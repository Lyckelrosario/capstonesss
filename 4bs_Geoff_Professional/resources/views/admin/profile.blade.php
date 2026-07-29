@extends('layouts.app')
@section('title', 'Admin Profile | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="profile-shell page-enter">
  <header>
    <p class="eyebrow">Account settings</p>
    <h1>Admin Profile</h1>
    <p>Manage your name, contact details, and password.</p>
  </header>

  <div class="profile-grid">
    <article class="profile-card details-card">
      <h2>Personal details</h2>
      <form method="post" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')
        <label for="name">Full name</label>
        <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="120" required>

        <label for="email">Email</label>
        <input id="email" value="{{ auth()->user()->email }}" disabled>
        <small class="field-help">Email cannot be changed through this form.</small>

        <label for="phone">Phone number</label>
        <input id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" maxlength="30">

        <button class="btn primary">Save changes</button>
      </form>
    </article>

    <article class="profile-card details-card">
      <h2>Change password</h2>
      <form method="post" action="{{ route('admin.profile.password') }}">
        @csrf
        @method('PUT')
        <label for="current_password">Current password</label>
        <input id="current_password" name="current_password" type="password" required>

        <label for="password">New password</label>
        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>

        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>

        <button class="btn secondary">Change password</button>
      </form>
    </article>
  </div>
</div>
</div>
@endsection
