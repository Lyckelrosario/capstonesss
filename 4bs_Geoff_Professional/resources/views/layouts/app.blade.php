<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', config('app.name'))</title>
<link rel="stylesheet" href="/css/style.css">
@viteReactRefresh
@vite('resources/js/app.tsx')
</head>
<body data-onboarding="{{ auth()->check() && auth()->user()->role === 'client' && !auth()->user()->onboarding_completed_at ? 'pending' : 'complete' }}">
<nav class="nav" aria-label="Main navigation">
  <a href="{{ route('home') }}" class="brand"><span class="brand-mark">4BS</span><span>Garage</span></a>
  <div class="navlinks">
    @auth
      <div class="nav-user">
        <span class="avatar avatar-small">
          @if(auth()->user()->avatar_url)<img src="{{ auth()->user()->avatar_url }}" alt="">@else{{ strtoupper(substr(auth()->user()->name,0,1)) }}@endif
        </span>
        <span class="nav-user-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role }}</small></span>
      </div>

      {{-- Notification Bell --}}
      <div class="notification-bell" id="notification-bell" style="position:relative" title="Notifications">
        <button class="btn-icon" id="notif-toggle" type="button" aria-label="Notifications" style="background:none;border:none;cursor:pointer;font-size:20px;padding:8px;border-radius:12px;transition:background var(--fast) ease;display:flex;align-items:center" onclick="toggleNotifications()">
          🔔
          <span id="notif-badge" class="notif-badge" style="display:none">0</span>
        </button>
        <div id="notif-dropdown" class="notif-dropdown" style="display:none">
          <div class="notif-header">
            <strong>Notifications</strong>
            <button class="btn small ghost" type="button" onclick="markAllRead()" style="font-size:11px;padding:4px 8px">Mark all read</button>
          </div>
          <div id="notif-list" class="notif-list">
            <div class="notif-empty">Loading...</div>
          </div>
        </div>
      </div>

      <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('client.dashboard') }}">Dashboard</a>
      <form class="logout-form" method="post" action="{{ route('logout') }}">@csrf<button class="btn red" type="submit">Sign out</button></form>
    @else
      <a href="{{ route('login') }}">Sign in</a><a class="btn" href="{{ route('register') }}">Create account</a>
    @endauth
  </div>
</nav>
<main class="container">
@if(session('success'))<div class="alert ok" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert err" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())
  <div class="alert err" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@yield('content')
</main>

@auth
{{-- Notification bell scripts --}}
<script>
let notifCount = 0;

function toggleNotifications() {
  const dropdown = document.getElementById('notif-dropdown');
  const isOpen = dropdown.style.display !== 'none';
  dropdown.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) loadNotifications();
}

async function loadNotifications() {
  try {
    const res = await fetch('/notifications', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
    const data = await res.json();
    const list = document.getElementById('notif-list');
    if (data.notifications.length === 0) {
      list.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
    } else {
      list.innerHTML = data.notifications.map(n => `
        <div class="notif-item ${n.isRead ? '' : 'notif-unread'}" onclick="markRead(${n.id})">
          <div style="font-weight:${n.isRead ? '500' : '700'};font-size:13px">${n.title}</div>
          ${n.body ? `<div style="font-size:12px;color:var(--text-soft);margin-top:2px">${n.body}</div>` : ''}
          <div style="font-size:10px;color:var(--muted-light);margin-top:4px">${n.createdAt}</div>
        </div>
      `).join('');
    }
  } catch {}
}

async function loadUnreadCount() {
  try {
    const res = await fetch('/notifications/unread-count', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
    const data = await res.json();
    const badge = document.getElementById('notif-badge');
    if (data.count > 0) {
      badge.textContent = data.count > 99 ? '99+' : data.count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  } catch {}
}

async function markRead(id) {
  try {
    await fetch('/notifications/' + id + '/read', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' } });
    loadNotifications();
    loadUnreadCount();
  } catch {}
}

async function markAllRead() {
  try {
    await fetch('/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' } });
    loadNotifications();
    loadUnreadCount();
  } catch {}
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  const bell = document.getElementById('notification-bell');
  if (bell && !bell.contains(e.target)) {
    document.getElementById('notif-dropdown').style.display = 'none';
  }
});

// Poll for unread count
document.addEventListener('DOMContentLoaded', function() {
  loadUnreadCount();
  setInterval(loadUnreadCount, 15000);
});
</script>
@endauth

</body>
</html>
