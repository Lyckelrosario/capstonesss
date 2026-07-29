@extends('layouts.app')
@section('title', 'Mechanics | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="page-enter">
  <p class="eyebrow">Workforce</p>
  <h1 class="page-title">Mechanics</h1>
  <p class="page-copy">Manage the mechanics and their specialties.</p>

  <div class="card">
   <form method="post" action="/admin/mechanics">@csrf
     <div class="grid">
       <div><label>Full name</label><input name="name" value="{{ old('name') }}" maxlength="120" placeholder="Mechanic name" required></div>
       <div><label>Specialty</label><input name="specialty" value="{{ old('specialty') }}" maxlength="160" placeholder="e.g. Brake & underchassis" required></div>
       <div><label>Experience</label><input name="experience" value="{{ old('experience') }}" maxlength="120" placeholder="e.g. 5 years"></div>
     </div>
     <button class="btn primary">Add Mechanic</button>
   </form>
  </div><br>

  <form class="card" method="get" style="padding:16px;margin-bottom:18px">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div style="flex:1;min-width:200px">
        <label for="search" style="margin-bottom:4px;font-size:12px">Search</label>
        <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Name or specialty..." style="margin:0">
      </div>
      <div style="min-width:140px">
        <label for="status" style="margin-bottom:4px;font-size:12px">Status</label>
        <select id="status" name="status" style="margin:0">
          <option value="">All</option>
          <option value="active" @selected(request('status')==='active')>Active</option>
          <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
      </div>
      <button class="btn primary" type="submit" style="margin-bottom:0">Filter</button>
      @if(request('search') || request('status'))
        <a class="btn ghost" href="{{ route('admin.mechanics') }}">Clear</a>
      @endif
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Name</th><th>Specialty</th><th>Experience</th><th>Status</th></tr></thead>
      <tbody>
      @forelse($mechanics as $m)
       <tr>
         <td><strong>{{ $m->name }}</strong></td>
         <td>{{ $m->specialty }}</td>
         <td>{{ $m->experience ?? 'N/A' }}</td>
         <td><span class="pill {{ $m->status }}">{{ ucfirst($m->status) }}</span></td>
       </tr>
      @empty<tr><td colspan="4">No mechanics yet.</td></tr>@endforelse
      </tbody>
    </table>
  </div>

  <div class="actions" style="margin-top:18px;justify-content:center">
    {{ $mechanics->appends(request()->only(['search', 'status']))->links() }}
  </div>
</div>
</div>
@endsection
