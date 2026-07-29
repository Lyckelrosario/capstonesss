@extends('layouts.app')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="page-enter">
  <p class="eyebrow">Operations overview</p>
  <h1 class="page-title">Admin Dashboard</h1>
  <p class="page-copy">Monitor appointments, inventory, and support activity at a glance.</p>

  <div class="grid stagger-children">
   <div class="card"><p class="muted">Pending</p><div class="metric">{{ $pending }}</div><a class="btn small ghost" href="{{ route('admin.appointments') }}">View appointments</a></div>
   <div class="card"><p class="muted">Approved</p><div class="metric">{{ $approved }}</div></div>
   <div class="card"><p class="muted">Completed</p><div class="metric">{{ $completed }}</div></div>
   <div class="card"><p class="muted">Cancelled</p><div class="metric">{{ $cancelled }}</div></div>
   <div class="card"><p class="muted">Low stock items</p><div class="metric">{{ $lowStock }}</div><a class="btn small ghost" href="{{ route('admin.products') }}">Manage inventory</a></div>
   <div class="card"><p class="muted">Chats waiting</p><div class="metric">{{ $waitingChats }}</div><a class="btn small primary" href="{{ route('admin.live-chat') }}">Open inbox</a></div>
   <div class="card"><p class="muted">Monthly revenue</p><div class="metric">₱{{ number_format($monthlyRevenue, 2) }}</div></div>
   <div class="card"><p class="muted">Active mechanics</p><div class="metric">{{ $totalMechanics }}</div></div>
  </div>

  <br>
  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap">
      <h3 style="margin:0">Recent Activity</h3>
      <a class="btn small ghost" href="{{ route('admin.analytics') }}">View full analytics</a>
    </div>
    @if($recentActivity->count())
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>When</th></tr></thead>
          <tbody>
            @foreach($recentActivity as $log)
            <tr>
              <td>{{ $log->user_name }}</td>
              <td><span class="pill">{{ str_replace('_', ' ', $log->action) }}</span></td>
              <td>{{ Str::limit($log->description, 60) }}</td>
              <td>{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p class="muted">No recent activity recorded.</p>
    @endif
  </div>
 </div>
</div>
@endsection
