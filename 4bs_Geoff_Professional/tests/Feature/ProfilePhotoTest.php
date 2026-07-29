<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_upload_a_valid_profile_photo(): void
    {
        Storage::fake('public');
        $client = User::create([
            'name' => 'Client', 'email' => 'photo@example.test', 'password' => 'Password123',
            'role' => 'client', 'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($client)->postJson('/client/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 640, 640),
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'avatarUrl']);
        $path = $client->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
