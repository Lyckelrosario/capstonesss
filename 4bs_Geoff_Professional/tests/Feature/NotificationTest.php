<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::create([
            'name' => 'Notif Client',
            'email' => 'notif@client.test',
            'phone' => '09171234567',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::create([
            'name' => 'Notif Admin',
            'email' => 'notif@admin.test',
            'phone' => '09181234567',
            'password' => 'Password123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function notification_service_can_send_to_user(): void
    {
        $service = app(NotificationService::class);

        $notification = $service->send(
            $this->client,
            'test_notification',
            'Test Title',
            'Test body content',
            '/client/dashboard'
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->client->id,
            'type' => 'test_notification',
            'title' => 'Test Title',
            'body' => 'Test body content',
            'action_url' => '/client/dashboard',
            'is_read' => false,
        ]);
    }

    /** @test */
    public function notification_service_sends_to_all_admins(): void
    {
        // Create a second admin
        User::create([
            'name' => 'Second Admin',
            'email' => 'admin2@notif.test',
            'phone' => '09199999999',
            'password' => 'Password123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $service = app(NotificationService::class);

        $service->sendToAdmins(
            'broadcast',
            'Admin Alert',
            'This is an admin broadcast'
        );

        $this->assertEquals(2, Notification::where('type', 'broadcast')->count());

        // Both admins should have received the notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'broadcast',
            'title' => 'Admin Alert',
        ]);
    }

    /** @test */
    public function user_can_list_own_notifications(): void
    {
        // Create some notifications
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'appointment_approved',
            'title' => 'Approved',
            'body' => 'Your appointment was approved.',
        ]);
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'appointment_reminder',
            'title' => 'Reminder',
            'body' => 'Your appointment is tomorrow.',
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'notifications');

        $response->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'type', 'title', 'body', 'actionUrl', 'isRead', 'createdAt'],
            ],
        ]);
    }

    /** @test */
    public function user_cannot_see_other_users_notifications(): void
    {
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'private',
            'title' => 'Private message',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'notifications');
    }

    /** @test */
    public function unread_count_returns_correct_number(): void
    {
        // Create 3 unread notifications
        for ($i = 0; $i < 3; $i++) {
            Notification::create([
                'user_id' => $this->client->id,
                'type' => 'test',
                'title' => "Notification {$i}",
            ]);
        }

        // Create 1 read notification
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'test',
            'title' => 'Old Notification',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $this->assertEquals(3, $service->unreadCount($this->client->id));

        // Also test via API endpoint
        $this->actingAs($this->client)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 3]);
    }

    /** @test */
    public function user_can_mark_single_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->client->id,
            'type' => 'test',
            'title' => 'Mark me read',
        ]);

        $this->assertFalse($notification->fresh()->is_read);

        $this->actingAs($this->client)
            ->postJson("/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function user_cannot_mark_other_users_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'admin_only',
            'title' => 'Admin secret',
        ]);

        $this->actingAs($this->client)
            ->postJson("/notifications/{$notification->id}/read")
            ->assertForbidden();
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'user_id' => $this->client->id,
                'type' => 'test',
                'title' => "Notif {$i}",
            ]);
        }

        $this->actingAs($this->client)
            ->postJson('/notifications/read-all')
            ->assertOk();

        $unreadCount = Notification::forUser($this->client->id)->unread()->count();
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function mark_all_read_only_affects_own_notifications(): void
    {
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'client_notif',
            'title' => 'Client message',
        ]);
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'admin_notif',
            'title' => 'Admin message',
        ]);

        $this->actingAs($this->client)
            ->postJson('/notifications/read-all')
            ->assertOk();

        // Client's notification should be read, admin's should still be unread
        $this->assertEquals(0, Notification::forUser($this->client->id)->unread()->count());
        $this->assertEquals(1, Notification::forUser($this->admin->id)->unread()->count());
    }
}
