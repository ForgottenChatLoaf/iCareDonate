@extends('layouts.public')

@section('title', 'About Our Mission')

@section('content')
<div class="container" style="padding: 50px 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <span class="status-pill status-info" style="margin-bottom: 12px;">Our Mission &amp; Purpose</span>
        <h1 style="font-size: 2.8rem; margin-bottom: 16px;">Connecting surplus essentials with verified human needs.</h1>
        <p class="lead" style="font-size: 1.15rem; margin-bottom: 32px;">
            iCareDonate is a structured, community-driven resource sharing platform built on a simple principle: 
            <strong>Strictly physical goods. Zero cash exchanges. 100% verified allocation.</strong>
        </p>

        <div class="grid-2" style="margin-bottom: 40px;">
            <div class="card">
                <div style="font-size: 28px; margin-bottom: 8px;">🎯</div>
                <h3>Our Vision</h3>
                <p>A society where usable physical resources—from school supplies and clothes to assistive medical devices and food grains—never go to waste while community members lack basic necessities.</p>
            </div>
            <div class="card">
                <div style="font-size: 28px; margin-bottom: 8px;">🤝</div>
                <h3>Dignity in Giving</h3>
                <p>We believe every beneficiary deserves respectful, transparent support. By validating requests with verifiable documentation and matching them with quality items, we preserve dignity and eliminate fraud.</p>
            </div>
        </div>

        <div class="card" style="padding: 32px; background: var(--surface-soft); border-left: 4px solid var(--brand-primary); margin-bottom: 40px;">
            <h3 style="color: var(--brand-primary);">Why Non-Cash Donations Only?</h3>
            <p>
                Many traditional charity portals face issues of transparency, misuse of funds, and administrative deductions. 
                By dealing exclusively in physical items, iCareDonate ensures that every contribution directly addresses an immediate, tangible need.
            </p>
            <p style="margin-bottom: 0;">
                Donors know exactly what item they donated, and requesters receive exactly what was promised via a secure pickup and claim code verification.
            </p>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <h2>Join the iCareDonate Network</h2>
            <p>Ready to make a genuine difference in someone's life today?</p>
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 20px;">
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">+ Post a Donation</a>
                <a href="{{ route('donations.index') }}" class="btn btn-outline btn-lg">Browse Available Items</a>
            </div>
        </div>
    </div>
</div>
@endsection
