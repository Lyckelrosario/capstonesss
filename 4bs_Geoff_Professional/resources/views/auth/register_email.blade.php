@extends('layouts.app')
@section('title', 'Create Account | 4BS Garage')
@section('content')
<form class="form" method="post" action="/register/send-code">@csrf
<h2>Create your account</h2><p class="muted">We will send a six-digit code to verify your email address.</p>
<label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
<button class="btn primary" style="width:100%">Send verification code</button>
</form>
@endsection
