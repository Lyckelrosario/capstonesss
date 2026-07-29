<nav class="side" aria-label="Client navigation">
 <div class="side-label">Client workspace</div>
 <a data-tour="dashboard" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}"><span class="nav-icon">▦</span>Dashboard</a>
 <a data-tour="appointment" class="{{ request()->routeIs('client.appointment') ? 'active' : '' }}" href="{{ route('client.appointment') }}"><span class="nav-icon">◫</span>Book Appointment</a>
 <a data-tour="chat" class="{{ request()->routeIs('client.chat') ? 'active' : '' }}" href="{{ route('client.chat') }}"><span class="nav-icon">◌</span>AI & Live Support</a>
 <a class="{{ request()->routeIs('client.feedback') ? 'active' : '' }}" href="{{ route('client.feedback') }}"><span class="nav-icon">★</span>Rate Service</a>
 <a data-tour="profile" class="{{ request()->routeIs('client.profile') ? 'active' : '' }}" href="{{ route('client.profile') }}"><span class="nav-icon">◎</span>My Profile</a>
</nav>
