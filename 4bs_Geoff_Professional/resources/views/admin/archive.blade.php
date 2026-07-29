@extends('layouts.app')
@section('title', 'Archive | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="page-enter">
  <p class="eyebrow">Service history</p>
  <h1 class="page-title">Completed Jobs Archive</h1>
  <p class="page-copy">Review completed jobs, ratings, and service history.</p>

  <form class="card" method="get" style="padding:16px;margin-bottom:18px">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div style="flex:1;min-width:200px">
        <label for="search" style="margin-bottom:4px;font-size:12px">Search</label>
        <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Client, service, mechanic..." style="margin:0">
      </div>
      <button class="btn primary" type="submit" style="margin-bottom:0">Search</button>
      @if(request('search'))
        <a class="btn ghost" href="{{ route('admin.archive') }}">Clear</a>
      @endif
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Client</th><th>Service</th><th>Mechanic</th><th>Date</th><th>Shop Rating</th><th>Mechanic Rating</th></tr></thead>
      <tbody>
      @forelse($appointments as $a)
      <tr>
        <td>{{ $a->client }}</td>
        <td>{{ $a->service }}</td>
        <td>{{ $a->mechanic }}</td>
        <td>{{ $a->appointment_date }}</td>
        <td>
          @if($a->shop_rating)
            <span class="star-display">{{ str_repeat('★', $a->shop_rating) }}</span>
          @else
            <span class="muted">Not rated</span>
          @endif
        </td>
        <td>
          @if($a->mechanic_rating)
            <span class="star-display">{{ str_repeat('★', $a->mechanic_rating) }}</span>
          @else
            <span class="muted">Not rated</span>
          @endif
        </td>
      </tr>
      @empty<tr><td colspan="6">No completed jobs yet.</td></tr>@endforelse
      </tbody>
    </table>
  </div>

  <div class="actions" style="margin-top:18px;justify-content:center">
    {{ $appointments->appends(request()->only(['search']))->links() }}
  </div>
</div>
</div>
@endsection
