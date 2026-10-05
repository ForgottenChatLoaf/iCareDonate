@extends('layouts.public')

@section('title', 'About Our Mission')

@section('content')

{{-- Page Hero --}}
<section class="pub-page-hero">
    <div class="container">
        <span class="status-pill status-info" style="margin-bottom:14px;">Our Mission &amp; Purpose</span>
        <h1>Connecting surplus essentials<br>with verified human needs.</h1>
        <p class="pub-hero-lead">
            iCareDonate is a structured, community-driven resource sharing platform built on a simple principle:
            <strong>Strictly physical goods. Zero cash exchanges. 100% verified allocation.</strong>
        </p>
    </div>
</section>

{{-- Vision & Dignity cards --}}
<section class="pub-section">
    <div class="container">
        <div class="grid-2" style="gap:24px;">
            <div class="card" style="padding:36px;">
                <div style="color:var(--brand-primary);margin-bottom:16px;"><x-icon name="check-circle" size="40" /></div>
                <h3>Our Vision</h3>
                <p>
                    A society where usable physical resources — from school supplies and clothes to assistive
                    medical devices and food grains — never go to waste while community members lack basic necessities.
                </p>
            </div>
            <div class="card" style="padding:36px;">
                <div style="color:var(--brand-accent);margin-bottom:16px;"><x-icon name="heart" size="40" /></div>
                <h3>Dignity in Giving</h3>
                <p>
                    We believe every beneficiary deserves respectful, transparent support. By validating requests
                    with verifiable documentation and matching them with quality items, we preserve dignity and eliminate fraud.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Why Non-Cash --}}
<section class="pub-section" style="background:var(--surface-soft);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <div class="container container-narrow">
        <div class="card" style="padding:40px;border-left:4px solid var(--brand-primary);">
            <h3 style="color:var(--brand-primary);margin-bottom:14px;">Why Non-Cash Donations Only?</h3>
            <p>
                Many traditional charity portals face issues of transparency, misuse of funds, and administrative
                deductions. By dealing exclusively in physical items, iCareDonate ensures that every contribution
                directly addresses an immediate, tangible need.
            </p>
            <p style="margin-bottom:0;">
                Donors know exactly what item they donated, and requesters receive exactly what was promised
                via a secure pickup and claim code verification.
            </p>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="pub-section">
    <div class="container" style="text-align:center;">
        <h2 style="margin-bottom:10px;">Join the iCareDonate Network</h2>
        <p style="margin-bottom:28px;">Ready to make a genuine difference in someone's life today?</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">
                <x-icon name="gift" size="18" /> Post a Donation
            </a>
            <a href="{{ route('donations.index') }}" class="btn btn-outline btn-lg">
                <x-icon name="search" size="18" /> Browse Available Items
            </a>
        </div>
    </div>
</section>

@endsection
