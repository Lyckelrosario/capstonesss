<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\GarageAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientChatController extends Controller
{
    public function page(Request $request): View
    {
        $conversation = $this->activeConversation($request);

        return view('client.chat', [
            'chatProps' => [
                'conversationId' => $conversation->id,
                'currentUser' => $this->userPayload($request),
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $conversation = $this->activeConversation($request)->load('assignedAdmin:id,name');

        return response()->json([
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $this->messagePayloads($conversation),
        ]);
    }

    public function send(Request $request, GarageAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = $this->activeConversation($request);
        $body = trim($data['message']);

        $userMessage = DB::transaction(function () use ($conversation, $request, $body) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status === ChatConversation::STATUS_CLOSED, 409, 'This conversation is closed.');

            $message = $locked->messages()->create([
                'sender_id' => $request->user()->id,
                'sender_type' => 'user',
                'body' => $body,
            ]);
            $locked->update(['last_message_at' => now()]);

            return $message;
        });

        $conversation->refresh();
        if (in_array($conversation->status, [ChatConversation::STATUS_WAITING, ChatConversation::STATUS_LIVE], true)) {
            return response()->json([
                'conversation' => $this->conversationPayload($conversation->load('assignedAdmin:id,name')),
                'messages' => [$this->messagePayload($userMessage)],
            ], 201);
        }

        $reply = $assistant->reply($conversation, $body);
        $aiMessage = DB::transaction(function () use ($conversation, $reply) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ChatConversation::STATUS_AI) {
                return null;
            }

            $message = $locked->messages()->create([
                'sender_type' => 'ai',
                'body' => $reply,
            ]);
            $locked->update(['last_message_at' => now()]);

            return $message;
        });

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh()->load('assignedAdmin:id,name')),
            'messages' => array_values(array_filter([
                $this->messagePayload($userMessage),
                $aiMessage ? $this->messagePayload($aiMessage) : null,
            ])),
        ], 201);
    }

    public function requestLiveSupport(Request $request): JsonResponse
    {
        $conversation = $this->activeConversation($request);

        $conversation = DB::transaction(function () use ($conversation) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status === ChatConversation::STATUS_CLOSED, 409, 'This conversation is closed.');

            if ($locked->status === ChatConversation::STATUS_AI) {
                $locked->update([
                    'status' => ChatConversation::STATUS_WAITING,
                    'live_requested_at' => now(),
                    'last_message_at' => now(),
                ]);
                $locked->messages()->create([
                    'sender_type' => 'system',
                    'body' => 'Live support requested. An administrator can now review the conversation and join when available.',
                ]);
            }

            return $locked->fresh()->load('assignedAdmin:id,name');
        });

        return response()->json(['conversation' => $this->conversationPayload($conversation)]);
    }

    public function returnToAi(Request $request): JsonResponse
    {
        $conversation = $this->activeConversation($request);

        $conversation = DB::transaction(function () use ($conversation, $request) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status === ChatConversation::STATUS_LIVE, 409, 'The administrator must end the active live support session first.');

            if ($locked->status === ChatConversation::STATUS_WAITING) {
                $locked->update([
                    'status' => ChatConversation::STATUS_AI,
                    'live_requested_at' => null,
                    'assigned_admin_id' => null,
                ]);
                $locked->messages()->create([
                    'sender_id' => $request->user()->id,
                    'sender_type' => 'system',
                    'body' => 'Live support request cancelled. AI assistance resumed.',
                ]);
            }

            return $locked->fresh()->load('assignedAdmin:id,name');
        });

        return response()->json(['conversation' => $this->conversationPayload($conversation)]);
    }

    private function activeConversation(Request $request): ChatConversation
    {
        $conversation = ChatConversation::where('user_id', $request->user()->id)
            ->where('status', '!=', ChatConversation::STATUS_CLOSED)
            ->latest('id')
            ->first();

        return $conversation ?: ChatConversation::create([
            'user_id' => $request->user()->id,
            'status' => ChatConversation::STATUS_AI,
            'subject' => 'Customer support',
            'last_message_at' => now(),
        ]);
    }

    private function messagePayloads(ChatConversation $conversation): array
    {
        return $conversation->messages()->orderBy('id')->limit(500)->get()->map(fn ($message) => $this->messagePayload($message))->all();
    }

    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'senderType' => $message->sender_type,
            'body' => $message->body,
            'createdAt' => $message->created_at?->toIso8601String(),
        ];
    }

    private function conversationPayload(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'assignedAdmin' => $conversation->assignedAdmin?->name,
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    private function userPayload(Request $request): array
    {
        return [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'avatarUrl' => $request->user()->avatar_url,
        ];
    }
}
