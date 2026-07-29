@extends('layouts.app')
@section('title', 'Book Appointment | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.client-sidebar')
<section>
 <p class="eyebrow">Service booking</p><h1 class="page-title">Book an appointment</h1><p class="page-copy">Choose an active mechanic and service. The system prevents two active bookings for the same mechanic and time.</p>
 <form class="card" method="post" action="{{ url('client/appointment') }}">@csrf
 <div class="grid">
  <div><label for="mechanic_id">Preferred mechanic</label><select id="mechanic_id" name="mechanic_id" required><option value="">Choose a mechanic</option>@foreach($mechanics as $m)<option value="{{ $m->id }}" @selected(old('mechanic_id')==$m->id)>{{ $m->name }} — {{ $m->specialty }}</option>@endforeach</select></div>
  <div><label for="service_id">Service</label><select id="service_id" name="service_id" required><option value="">Choose a service</option>@foreach($services as $s)<option value="{{ $s->id }}" @selected(old('service_id')==$s->id)>{{ $s->name }} — ₱{{ number_format($s->price,2) }}</option>@endforeach</select></div>
  <div><label for="vehicle_brand">Vehicle brand</label><input id="vehicle_brand" name="vehicle_brand" value="{{ old('vehicle_brand') }}" maxlength="120" required></div>
  <div><label for="vehicle_model">Vehicle model</label><input id="vehicle_model" name="vehicle_model" value="{{ old('vehicle_model') }}" maxlength="120" required></div>
  <div><label for="plate_number">Plate number</label><input id="plate_number" name="plate_number" value="{{ old('plate_number') }}" maxlength="50"></div>
  <div><label for="appointment_date">Date</label><input id="appointment_date" type="date" name="appointment_date" min="{{ now()->toDateString() }}" value="{{ old('appointment_date') }}" required></div>
  <div><label for="appointment_time">Time</label><input id="appointment_time" type="time" name="appointment_time" value="{{ old('appointment_time') }}" required></div>
 </div>
 <label for="issue_description">Describe the concern</label><textarea id="issue_description" name="issue_description" maxlength="3000" placeholder="Symptoms, sounds, warning lights, or when the issue started">{{ old('issue_description') }}</textarea>
 <label for="ai_diagnosis">AI assistant notes (optional)</label><textarea id="ai_diagnosis" name="ai_diagnosis" maxlength="5000" placeholder="Paste relevant guidance from your support conversation">{{ old('ai_diagnosis') }}</textarea>
 <button class="btn primary">Submit booking request</button>
 </form>
</section>
</div>
@endsection
