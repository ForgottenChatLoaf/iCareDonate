@extends('layouts.public')

@section('title', 'Community Resource Sharing Platform')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="status-pill status-verified" style="margin-bottom: 14px;">
                <x-icon name="check-circle" size="14" /> Verified Non-Cash Resource Donation Ecosystem
            </span>
            <h1>Give what you can. <br><span>Help where it matters.</span></h1>
            <p>
                Connect directly with schools, local relief centers, and families in genuine need. 
                Share educational supplies, non-perishable food, assistive medical equipment, and essential home goods with complete verification and transparency.
            </p>
            <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">
                    <x-icon name="gift" size="18" /> Post a Donation
                </a>
                <a href="{{ route('donations.index') }}" class="btn btn-outline btn-lg">
                    <x-icon name="search" size="18" /> Browse Available Items
                </a>
            </div>

            <!-- Dynamic Platform Statistics from MySQL -->
            <div class="hero-stats">
                <div class="stat-box">
                    <h3>{{ number_format($stats['total_items_shared']) }}+</h3>
                    <p>Physical Items Shared</p>
                </div>
                <div class="stat-box">
                    <h3>{{ number_format($stats['completed_claims']) }}</h3>
                    <p>Verified Claims Completed</p>
                </div>
                <div class="stat-box">
                    <h3>{{ number_format($stats['active_members']) }}</h3>
                    <p>Active Community Members</p>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: center;">
            <div class="card" style="width: 100%; max-width: 440px; background: var(--surface); border: 2px solid var(--border-strong); box-shadow: var(--shadow-lg);">
                <div style="font-family: var(--font-heading); font-size: 20px; color: var(--brand-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="shield" size="22" /> Transparent Process
                </div>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 12px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                        <div>
                            <div style="font-weight: 700; font-size: 14px;">Donation Verification</div>
                            <div style="font-size: 12px; color: var(--text-muted);">Administrators review condition, validity, and quantities before publication.</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                        <div>
                            <div style="font-weight: 700; font-size: 14px;">Proof-backed Requests</div>
                            <div style="font-size: 12px; color: var(--text-muted);">Requesters submit genuine certificates or proofs of urgency.</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                        <div>
                            <div style="font-weight: 700; font-size: 14px;">Secure Claim Codes</div>
                            <div style="font-size: 12px; color: var(--text-muted);">Automated claim codes and deadlines guarantee items reach the right hands.</div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border); text-align: center;">
                    <a href="{{ route('how-it-works') }}" style="font-size: 13px; font-weight: 600;">Learn full workflow details &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Resource Categories Section -->
<section style="padding: 60px 0; background: var(--bg);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
            <div>
                <span class="status-pill status-info" style="margin-bottom: 8px;">Categories</span>
                <h2>What Can You Share?</h2>
                <p>We focus on high-impact physical resources categorized for rapid matching.</p>
            </div>
            <a href="{{ route('categories') }}" class="btn btn-outline btn-sm">View All Categories &rarr;</a>
        </div>

        <div class="grid-3">
            @foreach($categories as $category)
                <a href="{{ route('donations.index', ['category' => $category->id]) }}" class="card" style="text-decoration: none; color: inherit;">
                    <div style="color: var(--brand-primary); margin-bottom: 12px;">
                        @switch($category->slug)
                            @case('food') <x-icon name="utensils" size="32" /> @break
                            @case('school-supplies') <x-icon name="book-open" size="32" /> @break
                            @case('clothing') <x-icon name="shirt" size="32" /> @break
                            @case('medical') <x-icon name="activity" size="32" /> @break
                            @case('household') <x-icon name="home" size="32" /> @break
                            @case('electronics') <x-icon name="laptop" size="32" /> @break
                            @default <x-icon name="package" size="32" />
                        @endswitch
                    </div>
                    <div style="font-family: var(--font-heading); font-size: 18px; color: var(--brand-primary); margin-bottom: 6px;">
                        {{ $category->name }}
                    </div>
                    <p style="font-size: 13px; color: var(--text-soft); margin-bottom: 14px; min-height: 40px;">
                        {{ Str::limit($category->description, 90) }}
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 10px;">
                        <span>Available items:</span>
                        <span class="status-pill status-approved">{{ $category->donations_count }} listings</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Available Items Preview Section -->
<section style="padding: 60px 0; background: var(--surface-soft); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
            <div>
                <span class="status-pill status-approved" style="margin-bottom: 8px;">Available Now</span>
                <h2>Approved Physical Donations</h2>
                <p>Browse verified resources currently waiting to be matched with beneficiaries.</p>
            </div>
            <a href="{{ route('donations.index') }}" class="btn btn-primary btn-sm">Browse All Donations &rarr;</a>
        </div>

        @if($featuredDonations->isEmpty())
            <div class="card" style="text-align: center; padding: 48px;">
                <p style="color: var(--text-muted);">No approved items currently listed. Be the first to post a resource!</p>
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-sm" style="margin-top: 12px;">
                    <x-icon name="gift" size="14" /> Post a Donation
                </a>
            </div>
        @else
            <div class="grid-3">
                @foreach($featuredDonations as $donation)
                    <div class="donation-card">
                        <div class="donation-card-img">
                            @if($donation->images->isNotEmpty())
                                <img src="{{ $donation->images->first()->url }}" alt="{{ $donation->title }}">
                            @else
                                <div style="color: var(--text-muted); opacity: 0.5;">
                                    @switch($donation->category->slug ?? '')
                                        @case('food') <x-icon name="utensils" size="48" /> @break
                                        @case('school-supplies') <x-icon name="book-open" size="48" /> @break
                                        @case('clothing') <x-icon name="shirt" size="48" /> @break
                                        @case('medical') <x-icon name="activity" size="48" /> @break
                                        @default <x-icon name="package" size="48" />
                                    @endswitch
                                </div>
                            @endif
                            <span class="status-pill status-approved" style="position: absolute; top: 12px; right: 12px; box-shadow: var(--shadow-sm);">
                                {{ $donation->quantity_available }} {{ $donation->unit }} available
                            </span>
                        </div>
                        <div class="donation-card-body">
                            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--brand-primary); margin-bottom: 4px;">
                                {{ $donation->category->name ?? 'General' }}
                            </div>
                            <h3 class="donation-card-title">{{ Str::limit($donation->title, 55) }}</h3>
                            <div class="donation-meta">
                                <span><x-icon name="map-pin" size="13" /> {{ $donation->pickup_city ?? 'Makati' }}</span>
                                <span><x-icon name="check" size="13" /> {{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</span>
                            </div>
                            <p style="font-size: 13px; color: var(--text-soft); margin-bottom: 16px;">
                                {{ Str::limit($donation->description, 85) }}
                            </p>
                            <div class="donation-card-footer">
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    By {{ $donation->donor->name ?? 'Verified Donor' }}
                                </span>
                                <a href="{{ route('donations.show', $donation->id) }}" class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- Call to Action Banner -->
<section style="padding: 60px 0; background: var(--bg);">
    <div class="container">
        <div class="card" style="background: linear-gradient(135deg, var(--surface-soft) 0%, var(--surface) 100%); border: 2px solid var(--border); padding: 48px; text-align: center; border-radius: var(--radius-xl);">
            <div style="max-width: 640px; margin: 0 auto;">
                <h2 style="font-size: 2.2rem; color: var(--brand-primary); margin-bottom: 12px;">Do You Have Surplus Physical Items to Share?</h2>
                <p style="font-size: 1.1rem; margin-bottom: 24px;">
                    Whether you are an individual with books and uniforms, or an organization with excess inventory, your surplus can directly uplift a community.
                </p>
                <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">
                        <x-icon name="gift" size="18" /> Post Donation Offer
                    </a>
                    <a href="{{ route('contact') }}" class="btn btn-outline btn-lg">
                        <x-icon name="mail" size="18" /> Partner With Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
