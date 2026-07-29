<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminChatController extends Controller
{
    public function page(Request $request): View
    {
        return view('admin.live-chat', [
            'chatProps' => [
                'adminId' => $request->user()->id,
                'adminName' => $request->user()->name,
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $conversations = ChatConversation::query()
            ->with(['user:id,name,email,avatar_path', 'assignedAdmin:id,name', 'latestMessage'])
            ->withCount('messages')
            ->orderByRaw("CASE status WHEN 'waiting_for_admin' THEN 1 WHEN 'live' THEN 2 WHEN 'ai' THEN 3 ELSE 4 END")
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get()
            ->map(fn ($conversation) => $this->conversationPayload($conversation));

        return response()->json(['conversations' => $conversations]);
    }

    public function show(ChatConversation $conversation): JsonResponse
    {
        $conversation->load(['user:id,name,email,avatar_path', 'assignedAdmin:id,name']);

        return response()->json([
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $conversation->messages()->orderBy('id')->limit(1000)->get()->map(fn ($message) => $this->messagePayload($message)),
        ]);
    }

    public function claim(Request $request, ChatConversation $conversation): JsonResponse
    {
        $claimed = DB::transaction(function () use ($request, $conversation) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [ChatConversation::STATUS_WAITING, ChatConversation::STATUS_LIVE], true), 409, 'This conversation is not waiting for live support.');
            abort_if($locked->assigned_admin_id && $locked->assigned_admin_id !== $request->user()->id, 409, 'Another administrator already claimed this conversation.');

            if ($locked->status === ChatConversation::STATUS_LIVE && $locked->assigned_admin_id === $request->user()->id) {
                return $locked->fresh()->load(['user:id,name,email,avatar_path', 'assignedAdmin:id,name']);
            }

            $locked->update([
                'assigned_admin_id' => $request->user()->id,
                'status' => ChatConversation::STATUS_LIVE,
                'claimed_at' => $locked->claimed_at ?? now(),
                'last_message_at' => now(),
            ]);

            $locked->messages()->create([
                'sender_id' => $request->user()->id,
                'sender_type' => 'system',
                'body' => $request->user()->name.' joined the live support conversation.',
            ]);

            return $locked->fresh()->load(['user:id,name,email,avatar_path', 'assignedAdmin:id,name']);
        });

        return response()->json(['conversation' => $this->conversationPayload($claimed)]);
    }

    public function send(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $message = DB::transaction(function () use ($request, $conversation, $data) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === ChatConversation::STATUS_LIVE, 409, 'This conversation is not in live support mode.');
            abort_unless($locked->assigned_admin_id === $request->user()->id, 403, 'Claim this conversation before replying.');

            $message = $locked->messages()->create([
                'sender_id' => $request->user()->id,
                'sender_type' => 'admin',
                'body' => trim($data['message']),
            ]);
            $locked->update(['last_message_at' => now()]);

            return $message;
        });

        return response()->json(['message' => $this->messagePayload($message)], 201);
    }

    public function end(Request $request, ChatConversation $conversation): JsonResponse
    {
        $updated = DB::transaction(function () use ($request, $conversation) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === ChatConversation::STATUS_LIVE, 409, 'This conversation is not in live support mode.');
            abort_unless($locked->assigned_admin_id === $request->user()->id, 403, 'Only the assigned administrator can end this live session.');

            $locked->messages()->create([
                'sender_id' => $request->user()->id,
                'sender_type' => 'system',
                'body' => 'Live support ended. The AI assistant is available again.',
            ]);
            $locked->update([
                'status' => ChatConversation::STATUS_AI,
                'assigned_admin_id' => null,
                'live_requested_at' => null,
                'claimed_at' => null,
                'last_message_at' => now(),
            ]);

            return $locked->fresh()->load(['user:id,name,email,avatar_path', 'assignedAdmin:id,name']);
        });

        return response()->json(['conversation' => $this->conversationPayload($updated)]);
    }

    public function close(Request $request, ChatConversation $conversation): JsonResponse
    {
        $updated = DB::transaction(function () use ($request, $conversation) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status === ChatConversation::STATUS_LIVE && $locked->assigned_admin_id !== $request->user()->id, 403, 'Only the assigned administrator can close this live session.');

            $locked->messages()->create([
                'sender_id' => $request->user()->id,
                'sender_type' => 'system',
                'body' => 'This support conversation was closed by an administrator.',
            ]);
            $locked->update([
                'status' => ChatConversation::STATUS_CLOSED,
                'closed_at' => now(),
                'last_message_at' => now(),
            ]);

            return $locked->fresh()->load(['user:id,name,email,avatar_path', 'assignedAdmin:id,name']);
        });

        return response()->json(['conversation' => $this->conversationPayload($updated)]);
    }

    private function conversationPayload(ChatConversation $conversation): array
    {
        $latestMessage = $conversation->relationLoaded('latestMessage')
            ? $conversation->latestMessage
            : $conversation->messages()->latest('id')->first();

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'subject' => $conversation->subject,
            'user' => [
                'id' => $conversation->user->id,
                'name' => $conversation->user->name,
                'email' => $conversation->user->email,
                'avatarUrl' => $conversation->user->avatar_url,
            ],
            'assignedAdmin' => $conversation->assignedAdmin ? [
                'id' => $conversation->assignedAdmin->id,
                'name' => $conversation->assignedAdmin->name,
            ] : null,
            'messageCount' => $conversation->messages_count ?? $conversation->messages()->count(),
            'lastMessage' => $latestMessage?->body,
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'senderType' => $message->sender_type,
            'senderId' => $message->sender_id,
            'body' => $message->body,
            'createdAt' => $message->created_at?->toIso8601String(),
        ];
    }
}
