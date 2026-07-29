<nav class="side" aria-label="Admin navigation">
 <div class="side-label">Operations</div>
 <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-icon">▦</span>Dashboard</a>
 <a class="{{ request()->routeIs('admin.live-chat') ? 'active' : '' }}" href="{{ route('admin.live-chat') }}"><span class="nav-icon">◌</span>Live Chat</a>
 <a class="{{ request()->routeIs('admin.appointments') ? 'active' : '' }}" href="{{ route('admin.appointments') }}"><span class="nav-icon">◫</span>Appointments</a>
 <a class="{{ request()->routeIs('admin.archive') ? 'active' : '' }}" href="{{ route('admin.archive') }}"><span class="nav-icon">▤</span>Archive</a>
 <a class="{{ request()->routeIs('admin.products') ? 'active' : '' }}" href="{{ route('admin.products') }}"><span class="nav-icon">◇</span>Inventory</a>
 <a class="{{ request()->routeIs('admin.mechanics') ? 'active' : '' }}" href="{{ route('admin.mechanics') }}"><span class="nav-icon">♟</span>Mechanics</a>
 <a class="{{ request()->routeIs('admin.services') ? 'active' : '' }}" href="{{ route('admin.services') }}"><span class="nav-icon">⚙</span>Services</a>
 <a class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}" href="{{ route('admin.analytics') }}"><span class="nav-icon">↗</span>Analytics</a>
 <div class="side-label" style="margin-top:16px">Account</div>
 <a class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}" href="{{ route('admin.profile') }}"><span class="nav-icon">◎</span>My Profile</a>
</nav>
