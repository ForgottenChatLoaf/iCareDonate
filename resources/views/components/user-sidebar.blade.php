<aside class="sidebar">
    <div class="sidebar-heading">Donor &amp; Requester Portal</div>

    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <x-icon name="dashboard" size="16" /> Dashboard
    </a>
    <a href="{{ route('donations.create') }}" class="sidebar-link {{ request()->routeIs('donations.create') ? 'active' : '' }}">
        <x-icon name="gift" size="16" /> Post a Donation
    </a>
    <a href="{{ route('user.donations') }}" class="sidebar-link {{ request()->routeIs('user.donations*') ? 'active' : '' }}">
        <x-icon name="heart" size="16" /> My Donations
    </a>
    <a href="{{ route('user.requests') }}" class="sidebar-link {{ request()->routeIs('user.requests*') ? 'active' : '' }}">
        <x-icon name="file-text" size="16" /> My Requests
    </a>
    <a href="{{ route('donations.index') }}" class="sidebar-link {{ request()->routeIs('donations.index') ? 'active' : '' }}">
        <x-icon name="search" size="16" /> Browse Items
    </a>

    <div class="sidebar-heading" style="margin-top: 14px;">Account &amp; Help</div>
    <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <x-icon name="bell" size="16" /> Notifications
        @php
            $unreadCount = auth()->user()?->notifications()->where('is_read', false)->count() ?? 0;
        @endphp
        @if($unreadCount > 0)
            <span class="status-pill status-pending" style="margin-left: auto; padding: 2px 6px; font-size: 11px;">{{ $unreadCount }}</span>
        @endif
    </a>
    <a href="{{ route('support.index') }}" class="sidebar-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
        <x-icon name="message" size="16" /> Support &amp; Concerns
    </a>
    <a href="{{ route('profile.index') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <x-icon name="user" size="16" /> Profile &amp; Settings
    </a>

    <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border);">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-link" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left;">
                <x-icon name="logout" size="16" /> Sign Out
            </button>
        </form>
    </div>
</aside>
