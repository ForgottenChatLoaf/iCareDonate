<aside class="sidebar" style="background-color: var(--surface);">
    <div style="padding: 10px 14px; margin-bottom: 8px; background: var(--surface-soft); border-radius: var(--radius-md); border: 1px solid var(--border);">
        <div style="font-size: 11px; font-weight: 700; color: var(--brand-primary); text-transform: uppercase;">Portal Role</div>
        <div style="font-weight: 700; font-size: 14px;">Administrator</div>
        <div style="font-size: 12px; color: var(--text-muted);">{{ auth()->user()->name }}</div>
    </div>

    <div class="sidebar-heading">Operations</div>
    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <x-icon name="dashboard" size="16" /> Dashboard
    </a>
    <a href="{{ route('admin.donations.index') }}" class="sidebar-link {{ request()->routeIs('admin.donations*') ? 'active' : '' }}">
        <x-icon name="heart" size="16" /> Review Donations
        @php $pendingDonations = \App\Models\Donation::where('status', 'pending_verification')->count(); @endphp
        @if($pendingDonations > 0)
            <span class="status-pill status-pending" style="margin-left: auto; padding: 2px 6px; font-size: 11px;">{{ $pendingDonations }}</span>
        @endif
    </a>
    <a href="{{ route('admin.requests.index') }}" class="sidebar-link {{ request()->routeIs('admin.requests*') ? 'active' : '' }}">
        <x-icon name="file-text" size="16" /> Review Requests
        @php $pendingRequests = \App\Models\ResourceRequest::whereIn('status', ['submitted', 'proof_review'])->count(); @endphp
        @if($pendingRequests > 0)
            <span class="status-pill status-pending" style="margin-left: auto; padding: 2px 6px; font-size: 11px;">{{ $pendingRequests }}</span>
        @endif
    </a>
    <a href="{{ route('admin.matches.index') }}" class="sidebar-link {{ request()->routeIs('admin.matches*') ? 'active' : '' }}">
        <x-icon name="repeat" size="16" /> Resource Matching
    </a>
    <a href="{{ route('admin.transactions.index') }}" class="sidebar-link {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}">
        <x-icon name="ticket" size="16" /> Transactions &amp; Claims
    </a>
    <a href="{{ route('admin.interviews.index') }}" class="sidebar-link {{ request()->routeIs('admin.interviews*') ? 'active' : '' }}">
        <x-icon name="mic" size="16" /> Interviews
    </a>

    <div class="sidebar-heading" style="margin-top: 14px;">Management</div>
    <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
        <x-icon name="users" size="16" /> User Moderation
    </a>
    <a href="{{ route('admin.categories.index') }}" class="sidebar-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
        <x-icon name="tag" size="16" /> Categories
    </a>
    <a href="{{ route('admin.support.index') }}" class="sidebar-link {{ request()->routeIs('admin.support*') ? 'active' : '' }}">
        <x-icon name="message" size="16" /> Support Tickets
    </a>
    <a href="{{ route('admin.faqs.index') }}" class="sidebar-link {{ request()->routeIs('admin.faqs*') ? 'active' : '' }}">
        <x-icon name="help-circle" size="16" /> FAQs
    </a>
    <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
        <x-icon name="bar-chart" size="16" /> Reports &amp; Stats
    </a>
    <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">
        <x-icon name="shield" size="16" /> System Audit Logs
    </a>

    <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border);">
        <a href="{{ route('home') }}" class="sidebar-link" style="margin-bottom: 6px;">
            <x-icon name="home" size="16" /> Public Site
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-link" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left; color: var(--danger);">
                <x-icon name="logout" size="16" /> Admin Sign Out
            </button>
        </form>
    </div>
</aside>
