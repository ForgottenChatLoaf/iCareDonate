<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="{{ route('home') }}" class="brand-link" style="margin-bottom: 14px;">
                    iCare<span>Donate</span>
                </a>
                <p>A transparent, community-driven resource sharing platform connecting generous donors of physical essentials directly with verified communities in need.</p>
                <p style="font-size: 13px; color: var(--text-muted);">Strictly physical resources — no monetary donations accepted.</p>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('about') }}">About Our Mission</a></li>
                    <li><a href="{{ route('how-it-works') }}">How It Works</a></li>
                    <li><a href="{{ route('categories') }}">Resource Categories</a></li>
                    <li><a href="{{ route('donations.index') }}">Available Items</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('faqs') }}">Frequently Asked Questions</a></li>
                    <li><a href="{{ route('contact') }}">Contact Us</a></li>
                    <li><a href="{{ route('support.create') }}">Submit a Concern</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Quick Actions</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('donations.create') }}">Post a Donation</a></li>
                    <li><a href="{{ route('donations.index') }}">Request an Item</a></li>
                    <li><a href="{{ route('login') }}">Member Sign In</a></li>
                    <li><a href="{{ route('register') }}">Create Account</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} iCareDonate. All rights reserved. Built for community resilience and direct impact.</span>
            <span>PHP 8.2 &middot; Laravel 12 &middot; MySQL &middot; Apache XAMPP</span>
        </div>
    </div>
</footer>
