@extends('layouts.app')
@section('content')
<section class="hero">
 <div class="hero-card">
  <h1>Professional automotive booking with smart pre-diagnosis.</h1>
  <p class="muted">Book appointments, choose your preferred mechanic, ask the AI assistant, and receive service updates from one clean system.</p>
  <div class="actions"><a class="btn" href="/register">Register as Client</a><a class="btn ghost" href="/login">Login</a></div>
 </div>
 <div class="hero-img"><div><h2>4BS Garage</h2><p>Reliable service. Organized workflow. Better customer experience.</p></div></div>
</section>
<div class="grid">
 <div class="card"><h3>AI Pre-diagnosis</h3><p class="muted">Assists clients with minor problems and stock questions.</p></div>
 <div class="card"><h3>Appointment Control</h3><p class="muted">Prevents duplicate mechanic booking for the same slot.</p></div>
 <div class="card"><h3>Inventory Monitoring</h3><p class="muted">Admin manually deducts products when items are bought.</p></div>
</div>
@endsection
