@extends('layouts.app')
@section('title', 'Complete Registration | 4BS Garage')
@section('content')
<form class="form" method="post" action="/register/complete">@csrf
<h2>Complete your profile</h2><p class="muted">Use a strong password with uppercase and lowercase letters and at least one number.</p>
<label for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required autofocus>
<label for="phone">Phone number</label><input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" maxlength="30" required>
<label for="password">Password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
<label for="captcha">Security check: What is {{ session('captcha_a') }} + {{ session('captcha_b') }}?</label><input id="captcha" name="captcha" inputmode="numeric" required>
<button class="btn primary" style="width:100%">Create account</button>
</form>
@endsection
