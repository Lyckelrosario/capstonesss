@extends('layouts.app')
@section('title', 'Sign In | 4BS Garage')
@section('content')
<form class="form" method="post" action="/login">@csrf
<h2>Welcome back</h2><p class="muted">Sign in to access your 4BS Garage workspace.</p>
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<label style="display:flex;align-items:center;gap:8px;font-weight:600"><input style="width:auto;margin:0" type="checkbox" name="remember" value="1">Keep me signed in</label>
<button class="btn primary" style="width:100%;margin-top:18px">Sign in</button>
<p class="muted">New customer? <a href="{{ route('register') }}">Create an account</a></p>
<p class="muted"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
</form>
@endsection
