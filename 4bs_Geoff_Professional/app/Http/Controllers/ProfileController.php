<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function page(Request $request): View
    {
        return view('client.profile', [
            'profileProps' => [
                'user' => [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'avatarUrl' => $request->user()->avatar_url,
                ],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'avatarUrl' => $request->user()->avatar_url,
            ],
        ]);
    }

    public function avatar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $data['avatar']->store('avatars', 'public');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return response()->json([
            'message' => 'Profile photo updated.',
            'avatarUrl' => $user->fresh()->avatar_url,
        ]);
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return response()->json(['message' => 'Tutorial completed.']);
    }
}
