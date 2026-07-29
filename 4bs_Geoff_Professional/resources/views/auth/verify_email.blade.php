@extends('layouts.app')
@section('title', 'Verify Email | 4BS Garage')
@section('content')
<form class="form" method="post" action="{{ url('register/verify-code') }}">@csrf
<h2>Verify your email</h2><p class="muted">Enter the six-digit code sent to <strong>{{ session('registration.email') }}</strong>. It expires after 10 minutes.</p>
<label for="code">Verification code</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
<button class="btn primary" style="width:100%">Verify email</button>
</form>
@endsection
