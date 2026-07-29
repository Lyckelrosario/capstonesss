<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Activity Admin',
            'email' => 'activity@admin.test',
            'phone' => '09181234567',
            'password' => 'Password123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->client = User::create([
            'name' => 'Activity Client',
            'email' => 'activity@client.test',
            'phone' => '09171234567',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function activity_logger_can_log_an_action_without_request(): void
    {
        $logger = app(ActivityLogger::class);

        $log = $logger->log(
            'test.action',
            'test_entity',
            42,
            'This is a test log entry'
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'action' => 'test.action',
            'entity_type' => 'test_entity',
            'entity_id' => 42,
            'description' => 'This is a test log entry',
            'user_id' => null, // No request provided
            'ip_address' => null,
        ]);
    }

    /** @test */
    public function activity_logger_can_log_with_authenticated_request(): void
    {
        $logger = app(ActivityLogger::class);

        $this->actingAs($this->admin);

        $log = $logger->log(
            'appointment.approved',
            'appointment',
            1,
            'Approved appointment #1',
            request()
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $this->admin->id,
            'action' => 'appointment.approved',
            'entity_type' => 'appointment',
            'entity_id' => 1,
            'description' => 'Approved appointment #1',
        ]);
    }

    /** @test */
    public function activity_log_records_user_relationship(): void
    {
        $logger = app(ActivityLogger::class);

        $this->actingAs($this->admin);
        $log = $logger->log('admin.login', null, null, 'Admin logged in', request());

        $this->assertNotNull($log->user);
        $this->assertEquals($this->admin->id, $log->user->id);
        $this->assertEquals($this->admin->name, $log->user->name);
    }

    /** @test */
    public function recent_scope_returns_latest_logs_limited(): void
    {
        $logger = app(ActivityLogger::class);

        // Create 5 logs without request
        for ($i = 0; $i < 5; $i++) {
            $logger->log("action.{$i}", 'test', $i, "Log entry {$i}");
        }

        $recent = ActivityLog::recent(3)->get();
        $this->assertCount(3, $recent);
        $this->assertEquals('action.4', $recent->first()->action);
    }

    /** @test */
    public function logger_recent_method_returns_with_user_relation(): void
    {
        $logger = app(ActivityLogger::class);

        $this->actingAs($this->admin);
        $logger->log('test.relation', 'test', 1, 'Testing relation loading', request());

        $recentLogs = $logger->recent(10);
        $this->assertCount(1, $recentLogs);
        $this->assertTrue($recentLogs->first()->relationLoaded('user'));
    }

    /** @test */
    public function activity_log_created_at_is_timestamp(): void
    {
        $logger = app(ActivityLogger::class);

        $log = $logger->log('test.timestamp', 'test', 1, 'Testing timestamps');

        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }

    /** @test */
    public function multiple_activity_logs_are_ordered_by_recency(): void
    {
        $logger = app(ActivityLogger::class);

        $logger->log('first', 'test', 1, 'First entry');
        $logger->log('second', 'test', 2, 'Second entry');
        $logger->log('third', 'test', 3, 'Third entry');

        $logs = ActivityLog::recent(10)->get();

        // Most recent should be first
        $this->assertEquals('third', $logs[0]->action);
        $this->assertEquals('second', $logs[1]->action);
        $this->assertEquals('first', $logs[2]->action);
    }
}
