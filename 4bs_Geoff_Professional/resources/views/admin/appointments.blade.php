@extends('layouts.app')
@section('title', 'Appointments | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<section class="page-enter">
 <p class="eyebrow">Service operations</p>
 <h1 class="page-title">Appointments</h1>
 <p class="page-copy">Approve valid requests, complete approved work, or cancel appointments that cannot proceed.</p>

 {{-- Search & Filter --}}
 <form class="card" method="get" style="padding:16px;margin-bottom:18px">
   <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
     <div style="flex:1;min-width:200px">
       <label for="search" style="margin-bottom:4px;font-size:12px">Search</label>
       <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Client, service, mechanic, brand..." style="margin:0">
     </div>
     <div style="min-width:140px">
       <label for="status" style="margin-bottom:4px;font-size:12px">Status</label>
       <select id="status" name="status" style="margin:0">
         <option value="">All statuses</option>
         <option value="pending" @selected(request('status')==='pending')>Pending</option>
         <option value="approved" @selected(request('status')==='approved')>Approved</option>
         <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
       </select>
     </div>
     <button class="btn primary" type="submit" style="margin-bottom:0">Filter</button>
     @if(request('search') || request('status'))
       <a class="btn ghost" href="{{ route('admin.appointments') }}">Clear</a>
     @endif
   </div>
 </form>

 <div class="table-wrap">
   <table class="table">
     <thead><tr><th>Client</th><th>Service</th><th>Mechanic</th><th>Vehicle</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
     <tbody>
     @forelse($appointments as $a)
     <tr>
       <td>{{ $a->client }}</td>
       <td>{{ $a->service }}</td>
       <td>{{ $a->mechanic }}</td>
       <td><small>{{ $a->vehicle_brand }} {{ $a->vehicle_model }}</small></td>
       <td>{{ $a->appointment_date }} {{ substr($a->appointment_time,0,5) }}</td>
       <td><span class="pill {{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
       <td><div class="actions">
        @if($a->status==='pending')<form method="post" action="/admin/appointments/{{ $a->id }}/approve">@csrf<button class="btn small primary">Approve</button></form>@endif
        @if($a->status==='approved')<form method="post" action="/admin/appointments/{{ $a->id }}/complete">@csrf<button class="btn small success">Complete</button></form>@endif
        @if(in_array($a->status,['pending','approved']))<form method="post" action="/admin/appointments/{{ $a->id }}/cancel">@csrf<button class="btn small red">Cancel</button></form>@endif
       </div></td>
     </tr>
     @empty<tr><td colspan="7">No appointments found.</td></tr>@endforelse
     </tbody>
   </table>
 </div>

 {{-- Pagination --}}
 <div class="actions" style="margin-top:18px;justify-content:center">
   {{ $appointments->appends(request()->only(['search', 'status']))->links() }}
 </div>
</section>
</div>
@endsection
