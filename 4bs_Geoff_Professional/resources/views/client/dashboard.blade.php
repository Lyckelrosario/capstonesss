@extends('layouts.app')
@section('title', 'Dashboard | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.client-sidebar')
<div class="page-enter">
  <p class="eyebrow">Client workspace</p>
  <h1 class="page-title">Dashboard</h1>
  <p class="page-copy">Track your appointments, service history, and notifications.</p>

  {{-- Notifications --}}
  @if($unreadNotifications > 0)
    <div class="card" style="border-left:3px solid var(--amber);margin-bottom:18px">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:24px">🔔</span>
          <div>
            <strong>You have {{ $unreadNotifications }} notification{{ $unreadNotifications !== 1 ? 's' : '' }}</strong>
            <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px">
              @foreach($recentNotifications as $n)
                <small style="color:var(--text-soft)">• {{ $n->title }} — <span class="muted">{{ $n->created_at->diffForHumans() }}</span></small>
              @endforeach
            </div>
          </div>
        </div>
        <small class="muted">Check your appointments below for status updates.</small>
      </div>
    </div>
  @endif

  {{-- Metrics --}}
  <div class="grid stagger-children" data-tour="dashboard-metrics">
    <div class="card"><p class="muted">Total appointments</p><div class="metric">{{ $total }}</div></div>
    <div class="card"><p class="muted">Upcoming</p><div class="metric">{{ $upcoming }}</div></div>
    <div class="card"><p class="muted">Awaiting your rating</p><div class="metric">{{ $toRate }}</div>@if($toRate > 0)<a class="btn small primary" href="{{ route('client.feedback') }}">Rate now</a>@endif</div>
  </div>

  <br>

  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <h3 style="margin:0">Recent Appointments</h3>
    <a class="btn primary" href="{{ route('client.appointment') }}">Book new appointment</a>
  </div>

  <br>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Service</th>
          <th>Mechanic</th>
          <th>Vehicle</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($appointments as $a)
      <tr>
        <td>{{ $a->service }}</td>
        <td>{{ $a->mechanic }}</td>
        <td><small>{{ $a->vehicle_brand }} {{ $a->vehicle_model }}</small></td>
        <td>{{ $a->appointment_date }} {{ substr($a->appointment_time,0,5) }}</td>
        <td><span class="pill {{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
        <td>
          <div class="actions">
            @if(in_array($a->status, ['pending', 'approved']))
              <button class="btn small ghost" onclick="this.nextElementSibling.style.display='block';this.style.display='none'" type="button">Reschedule</button>
              <form method="post" action="{{ route('client.appointment.reschedule', $a->id) }}" style="display:none">
                @csrf
                <input type="date" name="appointment_date" min="{{ now()->toDateString() }}" value="{{ $a->appointment_date }}" required style="display:inline-block;width:auto;max-width:140px;padding:6px 8px;font-size:12px;margin:0">
                <input type="time" name="appointment_time" value="{{ $a->appointment_time }}" required style="display:inline-block;width:auto;max-width:100px;padding:6px 8px;font-size:12px;margin:0">
                <button class="btn small primary" type="submit" style="font-size:11px">Update</button>
              </form>
              <form method="post" action="{{ route('client.appointment.cancel', $a->id) }}" onsubmit="return confirm('Cancel this appointment?')">
                @csrf
                <button class="btn small red" type="submit">Cancel</button>
              </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6">No appointments yet. <a href="{{ route('client.appointment') }}">Book your first service</a></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
</div>
@endsection
