<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $key = 'password-code:'.mb_strtolower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait '.RateLimiter::availableIn($key).' seconds before trying again.',
            ]);
        }

        $user = User::where('email', mb_strtolower($data['email']))->first();
        RateLimiter::hit($key, 300);

        // Use a neutral response so this endpoint does not reveal registered email addresses.
        if (! $user) {
            return redirect()->route('password.reset')->with('success', 'If the account exists, a reset code has been sent.');
        }

        $code = (string) random_int(100000, 999999);
        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);

        try {
            Mail::raw("Your 4BS Garage password reset code is {$code}. It expires in 10 minutes. Do not share it.", function ($mail) use ($user) {
                $mail->to($user->email)->subject('4BS Garage password reset');
            });
        } catch (Throwable) {
            $request->session()->forget('password_reset');
            return back()->with('error', 'The reset email could not be sent. Please contact support or check the mail configuration.');
        }

        return redirect()->route('password.reset')->with('success', 'If the account exists, a reset code has been sent.');
    }

    public function resetForm(): View
    {
        return view('auth.reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $reset = $request->session()->get('password_reset');
        if (! $reset || now()->timestamp > ($reset['expires_at'] ?? 0)) {
            $request->session()->forget('password_reset');
            return redirect()->route('password.request')->with('error', 'The reset code expired. Please request a new one.');
        }

        $attempts = (int) ($reset['attempts'] ?? 0) + 1;
        if ($attempts > 5) {
            $request->session()->forget('password_reset');
            return redirect()->route('password.request')->with('error', 'Too many incorrect attempts. Please request a new code.');
        }

        $reset['attempts'] = $attempts;
        $request->session()->put('password_reset', $reset);

        if (! hash_equals($reset['email'], mb_strtolower($data['email'])) || ! Hash::check($data['code'], $reset['code_hash'])) {
            return back()->with('error', 'The reset details are incorrect.');
        }

        $user = User::findOrFail($reset['user_id']);
        $user->update(['password' => $data['password']]);
        $request->session()->forget('password_reset');

        return redirect()->route('login')->with('success', 'Your password has been changed.');
    }
}
