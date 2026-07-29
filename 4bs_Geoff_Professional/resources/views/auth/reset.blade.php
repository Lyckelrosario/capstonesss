@extends('layouts.app')
@section('title', 'Choose New Password | 4BS Garage')
@section('content')
<form class="form" method="post" action="/reset-password">@csrf
<h2>Choose a new password</h2><p class="muted">The reset code expires after 10 minutes and can only be attempted five times.</p>
<label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', session('password_reset.email')) }}" autocomplete="email" required>
<label for="code">Six-digit code</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
<label for="password">New password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
<label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
<button class="btn primary" style="width:100%">Change password</button>
</form>
@endsection
