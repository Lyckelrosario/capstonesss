<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_live_support_and_admin_can_claim_and_reply(): void
    {
        $client = User::create([
            'name' => 'Client', 'email' => 'client@example.test', 'phone' => '0917',
            'password' => 'Password123', 'role' => 'client', 'email_verified_at' => now(),
        ]);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'phone' => '0918',
            'password' => 'Password123', 'role' => 'admin', 'email_verified_at' => now(),
        ]);

        $this->actingAs($client)
            ->postJson('/client/chat/request-live')
            ->assertOk()
            ->assertJsonPath('conversation.status', 'waiting_for_admin');

        $conversation = ChatConversation::where('user_id', $client->id)->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/admin/chat/conversations/{$conversation->id}/claim")
            ->assertOk()
            ->assertJsonPath('conversation.status', 'live');

        $this->actingAs($admin)
            ->postJson("/admin/chat/conversations/{$conversation->id}/messages", ['message' => 'How may I assist you?'])
            ->assertCreated()
            ->assertJsonPath('message.senderType', 'admin');

        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'status' => 'live',
            'assigned_admin_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'body' => 'How may I assist you?',
        ]);
    }

    public function test_client_cannot_access_admin_conversations(): void
    {
        $client = User::create([
            'name' => 'Client', 'email' => 'client2@example.test', 'password' => 'Password123',
            'role' => 'client', 'email_verified_at' => now(),
        ]);

        $this->actingAs($client)->getJson('/admin/chat/conversations')->assertForbidden();
    }
}
