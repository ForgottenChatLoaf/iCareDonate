@extends('layouts.app')

@section('body')
    <header class="site-header" style="background: var(--surface); border-bottom: 2px solid var(--brand-primary);">
        <div class="container navbar" style="max-width: 100%; padding: 0 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="{{ route('admin.dashboard') }}" class="brand-link">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    iCare<span>Admin</span>
                </a>
                <span class="status-pill status-verified" style="font-size: 11px;">Official Control Panel</span>
            </div>

            <div class="nav-actions">
                <button id="theme-toggle" class="theme-btn" title="Toggle theme">🌙</button>
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline" target="_blank">View Live Site ↗</a>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline" style="color: var(--danger);">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="dashboard-layout">
        @include('components.admin-sidebar')

        <main class="dashboard-main">
            @if(session('success'))
                <div class="alert alert-success alert-auto-dismiss">
                    <span>✓ {{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-auto-dismiss">
                    <span>⚠️ {{ session('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@endsection
