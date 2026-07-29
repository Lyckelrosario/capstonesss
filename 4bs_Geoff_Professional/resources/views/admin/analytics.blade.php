@extends('layouts.app')
@section('title', 'Analytics | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div class="page-enter">
  <p class="eyebrow">Data & Insights</p>
  <h1 class="page-title">Analytics</h1>
  <p class="page-copy">Track service performance, sales, revenue, and customer feedback.</p>

  {{-- KPI Cards --}}
  <div class="grid stagger-children">
    <div class="card">
      <p class="muted">Total Appointments</p>
      <div class="metric">{{ $appointmentStats['total'] }}</div>
    </div>
    <div class="card">
      <p class="muted">Completion Rate</p>
      <div class="metric">{{ $conversionRate }}%</div>
    </div>
    <div class="card">
      <p class="muted">Total Revenue</p>
      <div class="metric">₱{{ number_format($totalRevenue, 0) }}</div>
    </div>
    <div class="card">
      <p class="muted">Average Shop Rating</p>
      <div class="metric">{{ $avgRatings?->avg_shop ?? '—' }}<span style="font-size:16px;color:var(--muted)">/5</span></div>
      <small class="muted">Based on {{ $avgRatings?->total_reviews ?? 0 }} reviews</small>
    </div>
  </div>

  <br>

  {{-- Appointment Funnel --}}
  <div class="grid">
    <div class="card">
      <h3>Appointment Funnel</h3>
      <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
        <div style="display:flex;align-items:center;gap:12px">
          <span style="width:80px;font-weight:700;font-size:13px">Pending</span>
          <div style="flex:1;height:24px;background:#fdf3db;border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $appointmentStats['total'] > 0 ? ($appointmentStats['pending'] / $appointmentStats['total']) * 100 : 0 }}%;background:#d4921e;border-radius:6px;min-width:0"></div>
          </div>
          <span style="font-weight:800;font-size:15px;min-width:40px;text-align:right">{{ $appointmentStats['pending'] }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <span style="width:80px;font-weight:700;font-size:13px">Approved</span>
          <div style="flex:1;height:24px;background:#ebeef4;border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $appointmentStats['total'] > 0 ? ($appointmentStats['approved'] / $appointmentStats['total']) * 100 : 0 }}%;background:#2a4a6a;border-radius:6px;min-width:0"></div>
          </div>
          <span style="font-weight:800;font-size:15px;min-width:40px;text-align:right">{{ $appointmentStats['approved'] }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <span style="width:80px;font-weight:700;font-size:13px">Completed</span>
          <div style="flex:1;height:24px;background:#ecf4ee;border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $appointmentStats['total'] > 0 ? ($appointmentStats['completed'] / $appointmentStats['total']) * 100 : 0 }}%;background:#3a7d5a;border-radius:6px;min-width:0"></div>
          </div>
          <span style="font-weight:800;font-size:15px;min-width:40px;text-align:right">{{ $appointmentStats['completed'] }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <span style="width:80px;font-weight:700;font-size:13px">Cancelled</span>
          <div style="flex:1;height:24px;background:#fdf0ed;border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $appointmentStats['total'] > 0 ? ($appointmentStats['cancelled'] / $appointmentStats['total']) * 100 : 0 }}%;background:#b54338;border-radius:6px;min-width:0"></div>
          </div>
          <span style="font-weight:800;font-size:15px;min-width:40px;text-align:right">{{ $appointmentStats['cancelled'] }}</span>
        </div>
      </div>
    </div>

    <div class="card">
      <h3>Most Requested Services</h3>
      @forelse($services as $s)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
          <span>{{ $s->name }}</span>
          <span style="font-weight:800;font-size:18px;color:var(--amber)">{{ $s->total }}</span>
        </div>
      @empty
        <p class="muted">No service data yet.</p>
      @endforelse
    </div>
  </div>

  <br>

  {{-- Monthly trend & Brands --}}
  <div class="grid">
    <div class="card">
      <h3>Monthly Appointments (Last 12 Months)</h3>
      @forelse($monthlyAppointments as $m)
        <div style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid #f3efe8">
          <span style="min-width:80px;font-weight:600;font-size:13px">{{ DateTime::createFromFormat('!m', $m->month)->format('M') }} {{ $m->year }}</span>
          <div style="flex:1;height:16px;background:#ede8df;border-radius:4px;overflow:hidden">
            <div style="height:100%;width:{{ $monthlyAppointments->max('total') > 0 ? ($m->total / $monthlyAppointments->max('total')) * 100 : 0 }}%;background:linear-gradient(90deg,var(--amber),var(--amber-dark));border-radius:4px;min-width:0"></div>
          </div>
          <span style="font-size:13px;font-weight:700;min-width:60px;text-align:right">{{ $m->total }} ({{ $m->completed }} done)</span>
        </div>
      @empty
        <p class="muted">No appointment data yet.</p>
      @endforelse
    </div>

    <div class="card">
      <h3>Top Product Brands by Sales</h3>
      @forelse($brands as $b)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
          <div><strong>{{ $b->brand }}</strong><br><small class="muted">₱{{ number_format($b->total_revenue ?? 0, 0) }} revenue</small></div>
          <span style="font-weight:800;font-size:18px;color:var(--dark)">{{ $b->total_qty }} sold</span>
        </div>
      @empty
        <p class="muted">No product sales yet.</p>
      @endforelse
    </div>
  </div>

  {{-- Average Ratings --}}
  @if($avgRatings && $avgRatings->total_reviews > 0)
  <br>
  <div class="card">
    <h3>Customer Satisfaction</h3>
    <div class="grid" style="margin-top:12px">
      <div style="text-align:center">
        <div class="metric">{{ $avgRatings->avg_shop }} <span style="font-size:20px;color:var(--muted)">/ 5</span></div>
        <p class="muted">Average Shop Rating</p>
        <div class="star-display" style="font-size:28px">{{ str_repeat('★', (int) round($avgRatings->avg_shop)) }}{{ str_repeat('☆', 5 - (int) round($avgRatings->avg_shop)) }}</div>
      </div>
      <div style="text-align:center">
        <div class="metric">{{ $avgRatings->avg_mechanic }} <span style="font-size:20px;color:var(--muted)">/ 5</span></div>
        <p class="muted">Average Mechanic Rating</p>
        <div class="star-display" style="font-size:28px">{{ str_repeat('★', (int) round($avgRatings->avg_mechanic)) }}{{ str_repeat('☆', 5 - (int) round($avgRatings->avg_mechanic)) }}</div>
      </div>
      <div style="text-align:center">
        <div class="metric">{{ $avgRatings->total_reviews }}</div>
        <p class="muted">Total Reviews</p>
      </div>
    </div>
  </div>
  @endif
</div>
</div>
@endsection
