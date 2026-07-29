@extends('layouts.app')
@section('title', 'Services | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="page-enter">
  <p class="eyebrow">Service catalog</p>
  <h1 class="page-title">Services</h1>
  <p class="page-copy">Manage the services offered by the garage.</p>

  <div class="card">
   <form method="post" action="/admin/services">@csrf
     <div class="grid">
       <div><label>Service name</label><input name="name" value="{{ old('name') }}" maxlength="160" required></div>
       <div><label>Price (₱)</label><input name="price" type="number" min="0" step=".01" value="{{ old('price') }}" required></div>
       <div><label>Duration (minutes)</label><input name="duration_minutes" type="number" min="15" max="1440" value="{{ old('duration_minutes',60) }}" required></div>
     </div>
     <label>Description</label><textarea name="description" maxlength="3000" placeholder="Brief description of the service">{{ old('description') }}</textarea>
     <button class="btn primary">Add Service</button>
   </form>
  </div><br>

  <form class="card" method="get" style="padding:16px;margin-bottom:18px">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div style="flex:1;min-width:200px">
        <label for="search" style="margin-bottom:4px;font-size:12px">Search</label>
        <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Service name..." style="margin:0">
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
        <a class="btn ghost" href="{{ route('admin.services') }}">Clear</a>
      @endif
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Service</th><th>Description</th><th>Price</th><th>Duration</th><th>Status</th></tr></thead>
      <tbody>
      @forelse($services as $s)
       <tr>
         <td><strong>{{ $s->name }}</strong></td>
         <td><small>{{ Str::limit($s->description, 60) }}</small></td>
         <td>₱{{ number_format($s->price, 2) }}</td>
         <td>{{ $s->duration_minutes }} mins</td>
         <td><span class="pill {{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
       </tr>
      @empty<tr><td colspan="5">No services yet.</td></tr>@endforelse
      </tbody>
    </table>
  </div>

  <div class="actions" style="margin-top:18px;justify-content:center">
    {{ $services->appends(request()->only(['search', 'status']))->links() }}
  </div>
</div>
</div>
@endsection
