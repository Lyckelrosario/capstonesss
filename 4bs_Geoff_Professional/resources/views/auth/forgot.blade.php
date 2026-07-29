@extends('layouts.app')
@section('title', 'Reset Password | 4BS Garage')
@section('content')
<form class="form" method="post" action="/forgot-password">@csrf
<h2>Reset your password</h2><p class="muted">Enter your email. If an account exists, we will send a six-digit reset code.</p>
<label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
<button class="btn primary" style="width:100%">Send reset code</button>
</form>
@endsection
