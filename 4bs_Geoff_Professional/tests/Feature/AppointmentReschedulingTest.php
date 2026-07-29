<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentReschedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $admin;
    private int $mechanicId;
    private int $serviceId;
    private int $appointmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::create([
            'name' => 'Test Client',
            'email' => 'client@reschedule.test',
            'phone' => '09171234567',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@reschedule.test',
            'phone' => '09181234567',
            'password' => 'Password123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create a mechanic
        $this->mechanicId = DB::table('mechanics')->insertGetId([
            'name' => 'Juan Mechanic',
            'specialty' => 'Engine',
            'experience' => '5 years',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a service
        $this->serviceId = DB::table('services')->insertGetId([
            'name' => 'Oil Change',
            'description' => 'Standard oil change',
            'price' => 500.00,
            'duration_minutes' => 60,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an appointment (pending)
        $this->appointmentId = DB::table('appointments')->insertGetId([
            'user_id' => $this->client->id,
            'mechanic_id' => $this->mechanicId,
            'service_id' => $this->serviceId,
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Vios',
            'plate_number' => 'ABC123',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '10:00',
            'issue_description' => 'Engine noise',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function client_can_reschedule_own_pending_appointment(): void
    {
        $newDate = now()->addDays(10)->toDateString();
        $newTime = '14:30';

        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => $newTime,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointmentId,
            'appointment_date' => $newDate,
            'appointment_time' => $newTime,
            'status' => 'pending', // Reset to pending for re-approval
        ]);
    }

    /** @test */
    public function client_can_reschedule_own_approved_appointment(): void
    {
        // First approve the appointment
        DB::table('appointments')->where('id', $this->appointmentId)->update([
            'status' => 'approved',
            'updated_at' => now(),
        ]);

        $newDate = now()->addDays(7)->toDateString();
        $newTime = '09:00';

        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => $newTime,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointmentId,
            'appointment_date' => $newDate,
            'appointment_time' => $newTime,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function client_cannot_reschedule_another_clients_appointment(): void
    {
        $otherClient = User::create([
            'name' => 'Other Client',
            'email' => 'other@reschedule.test',
            'phone' => '09199999999',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($otherClient)
            ->postJson("/client/appointment/{$this->appointmentId}/reschedule", [
                'appointment_date' => now()->addDays(10)->toDateString(),
                'appointment_time' => '11:00',
            ])
            ->assertNotFound();
    }

    /** @test */
    public function client_cannot_reschedule_to_conflicting_slot(): void
    {
        // Create another appointment that blocks the time slot
        $otherUserId = User::create([
            'name' => 'Other User',
            'email' => 'otheruser@reschedule.test',
            'phone' => '09171111111',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ])->id;

        DB::table('appointments')->insert([
            'user_id' => $otherUserId,
            'mechanic_id' => $this->mechanicId,
            'service_id' => $this->serviceId,
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Civic',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to reschedule to the same slot
        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/reschedule", [
                'appointment_date' => now()->addDays(5)->toDateString(),
                'appointment_time' => '10:00',
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function client_cannot_reschedule_completed_appointment(): void
    {
        DB::table('appointments')->where('id', $this->appointmentId)->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/reschedule", [
                'appointment_date' => now()->addDays(10)->toDateString(),
                'appointment_time' => '11:00',
            ])
            ->assertNotFound();
    }

    /** @test */
    public function client_can_cancel_own_pending_appointment(): void
    {
        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/cancel")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointmentId,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function client_can_cancel_own_approved_appointment(): void
    {
        DB::table('appointments')->where('id', $this->appointmentId)->update([
            'status' => 'approved',
            'updated_at' => now(),
        ]);

        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/cancel")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointmentId,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function client_cannot_cancel_completed_appointment(): void
    {
        DB::table('appointments')->where('id', $this->appointmentId)->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        $this->actingAs($this->client)
            ->postJson("/client/appointment/{$this->appointmentId}/cancel")
            ->assertNotFound();
    }

    /** @test */
    public function client_cannot_cancel_another_clients_appointment(): void
    {
        $otherClient = User::create([
            'name' => 'Other Client 2',
            'email' => 'other2@reschedule.test',
            'phone' => '09198888888',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($otherClient)
            ->postJson("/client/appointment/{$this->appointmentId}/cancel")
            ->assertNotFound();
    }
}
