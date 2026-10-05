<header class="site-header">
    <div class="container navbar">
        <a href="{{ route('home') }}" class="brand-link">
            <x-icon name="heart" size="26" style="color: var(--brand-accent);" />
            iCare<span>Donate</span>
        </a>

        <ul class="nav-links">
            <li>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <x-icon name="home" size="15" /> Home
                </a>
            </li>
            <li>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">
                    <x-icon name="info" size="15" /> About
                </a>
            </li>
            <li>
                <a href="{{ route('how-it-works') }}" class="{{ request()->routeIs('how-it-works') ? 'active' : '' }}">
                    <x-icon name="repeat" size="15" /> How It Works
                </a>
            </li>
            <li>
                <a href="{{ route('categories') }}" class="{{ request()->routeIs('categories') ? 'active' : '' }}">
                    <x-icon name="tag" size="15" /> Categories
                </a>
            </li>
            <li>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">
                    <x-icon name="mail" size="15" /> Contact
                </a>
            </li>
        </ul>

        <div class="nav-actions">
            <button id="theme-toggle" class="theme-btn" title="Toggle dark mode">
                <x-icon name="moon" size="16" />
            </button>

            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline">
                        <x-icon name="shield" size="14" /> Admin Portal
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline">
                        <x-icon name="dashboard" size="14" /> Dashboard
                    </a>
                    <a href="{{ route('donations.create') }}" class="btn btn-sm btn-accent">
                        <x-icon name="gift" size="14" /> Donate
                    </a>
                @endif
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline">
                        <x-icon name="logout" size="14" /> Sign Out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline">
                    <x-icon name="log-in" size="14" /> Sign In
                </a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-primary">
                    <x-icon name="user" size="14" /> Sign Up
                </a>
            @endauth
        </div>
    </div>
</header>
