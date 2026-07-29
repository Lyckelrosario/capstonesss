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

class RegistrationController extends Controller
{
    public function email(): View
    {
        return view('auth.register_email');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
        ]);

        $key = 'registration-code:'.mb_strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait '.RateLimiter::availableIn($key).' seconds before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $request->session()->put('registration', [
            'email' => mb_strtolower($data['email']),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);

        try {
            Mail::raw("Your 4BS Garage verification code is {$code}. It expires in 10 minutes. Do not share it.", function ($mail) use ($data) {
                $mail->to($data['email'])->subject('4BS Garage email verification');
            });
        } catch (Throwable) {
            $request->session()->forget('registration');
            return back()->withInput()->with('error', 'The verification email could not be sent. Please contact support or check the mail configuration.');
        }

        RateLimiter::hit($key, 300);

        return redirect()->route('register.verify')->with('success', 'A verification code was sent to your email.');
    }

    public function verify(): View|RedirectResponse
    {
        if (! session('registration.email')) {
            return redirect()->route('register');
        }

        return view('auth.verify_email');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $registration = $request->session()->get('registration');

        if (! $registration || now()->timestamp > ($registration['expires_at'] ?? 0)) {
            $request->session()->forget('registration');
            return redirect()->route('register')->with('error', 'The verification code expired. Please request a new one.');
        }

        $attempts = (int) ($registration['attempts'] ?? 0) + 1;
        if ($attempts > 5) {
            $request->session()->forget('registration');
            return redirect()->route('register')->with('error', 'Too many incorrect attempts. Please request a new code.');
        }

        $registration['attempts'] = $attempts;
        $request->session()->put('registration', $registration);

        if (! Hash::check($data['code'], $registration['code_hash'])) {
            return back()->with('error', 'The verification code is incorrect.');
        }

        $request->session()->put('registration.verified', true);
        $this->newCaptcha($request);

        return redirect()->route('register.complete')->with('success', 'Email verified. Complete your account details.');
    }

    public function complete(): View|RedirectResponse
    {
        if (! session('registration.verified')) {
            return redirect()->route('register');
        }

        return view('auth.complete_register');
    }

    public function store(Request $request): RedirectResponse
    {
        $registration = $request->session()->get('registration');
        if (! ($registration['verified'] ?? false)) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'captcha' => ['required', 'integer'],
        ]);

        $expectedCaptcha = (int) $request->session()->get('captcha_a') + (int) $request->session()->get('captcha_b');
        if ((int) $data['captcha'] !== $expectedCaptcha) {
            $this->newCaptcha($request);
            return back()->withInput($request->except('password', 'password_confirmation'))->with('error', 'The security answer is incorrect.');
        }

        User::create([
            'name' => $data['name'],
            'email' => $registration['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $request->session()->forget(['registration', 'captcha_a', 'captcha_b']);

        return redirect()->route('login')->with('success', 'Your account was created. You can now sign in.');
    }

    private function newCaptcha(Request $request): void
    {
        $request->session()->put([
            'captcha_a' => random_int(1, 9),
            'captcha_b' => random_int(1, 9),
        ]);
    }
}
