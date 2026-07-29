<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Original Admin',
            'email' => 'admin@profile.test',
            'phone' => '09181234567',
            'password' => 'CurrentPass1',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_view_profile_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Original Admin')
            ->assertSee('admin@profile.test');
    }

    /** @test */
    public function admin_can_update_name(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/profile')
            ->put('/admin/profile', [
                'name' => 'Updated Admin',
                'phone' => '09191234567',
            ])
            ->assertRedirect('/admin/profile')
            ->assertSessionHas('success', 'Profile updated.');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Updated Admin',
            'phone' => '09191234567',
        ]);
    }

    /** @test */
    public function admin_can_update_name_only(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/profile')
            ->put('/admin/profile', [
                'name' => 'Name Only Change',
                'phone' => null,
            ])
            ->assertRedirect('/admin/profile');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Name Only Change',
            'phone' => null,
        ]);
    }

    /** @test */
    public function admin_name_validation_requires_string(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/admin/profile', [
                'name' => '',
                'phone' => '09191234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function admin_name_validation_max_length(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/admin/profile', [
                'name' => str_repeat('a', 121),
                'phone' => '09191234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function admin_can_change_password(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/profile')
            ->put('/admin/profile/password', [
                'current_password' => 'CurrentPass1',
                'password' => 'NewSecurePass1',
                'password_confirmation' => 'NewSecurePass1',
            ])
            ->assertRedirect('/admin/profile')
            ->assertSessionHas('success', 'Password changed.');

        // Verify the new password works
        $this->assertTrue(Hash::check('NewSecurePass1', $this->admin->fresh()->password));
    }

    /** @test */
    public function admin_cannot_change_password_with_wrong_current(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/admin/profile/password', [
                'current_password' => 'WrongPass1',
                'password' => 'NewSecurePass1',
                'password_confirmation' => 'NewSecurePass1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        // Password should remain unchanged
        $this->assertTrue(Hash::check('CurrentPass1', $this->admin->fresh()->password));
    }

    /** @test */
    public function admin_cannot_change_password_with_mismatched_confirmation(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/admin/profile/password', [
                'current_password' => 'CurrentPass1',
                'password' => 'NewSecurePass1',
                'password_confirmation' => 'DifferentPass1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function admin_password_must_meet_complexity_requirements(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/admin/profile/password', [
                'current_password' => 'CurrentPass1',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function client_cannot_access_admin_profile(): void
    {
        $client = User::create([
            'name' => 'Client User',
            'email' => 'client@profile.test',
            'phone' => '09171234567',
            'password' => 'Password123',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($client)
            ->get('/admin/profile')
            ->assertForbidden();
    }

    /** @test */
    public function guest_cannot_access_admin_profile(): void
    {
        $this->get('/admin/profile')
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_update_admin_profile(): void
    {
        $this->putJson('/admin/profile', ['name' => 'Hacker'])
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_view_profile_json_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Original Admin')
            ->assertSee('admin@profile.test');
    }
}
