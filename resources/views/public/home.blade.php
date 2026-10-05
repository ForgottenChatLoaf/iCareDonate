@extends('layouts.public')

@section('title', 'Community Resource Sharing Platform')

@section('content')

{{-- ============================================================
     HERO SECTION  (wireframe: big h1 · lead copy · cta buttons · trust line)
     ============================================================ --}}
<section class="pub-hero">
    <div class="container pub-hero-grid">

        {{-- Left: Text content --}}
        <div class="pub-hero-text">
            <span class="pub-hero-badge">
                <x-icon name="shield" size="14" />
                Verified Non-Cash Resource Donation Ecosystem
            </span>

            <h1>Give with care.<br><span style="color:var(--brand-accent);">Receive with dignity.</span></h1>

            <p class="pub-hero-lead">
                iCareDonate connects people who have items to share with individuals and
                communities who genuinely need them — with full admin verification every step of the way.
            </p>

            <div class="pub-hero-btns">
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">
                    <x-icon name="gift" size="18" /> Post a Donation
                </a>
                <a href="{{ route('requests.create') }}" class="btn btn-outline btn-lg">
                    <x-icon name="file-text" size="18" /> Request an Item
                </a>
            </div>

            <p class="pub-hero-trust">
                <x-icon name="check" size="14" />
                Every donation and request is verified by an administrator.
            </p>
        </div>

        {{-- Right: Stats panel (wireframe "hframe / hbox") --}}
        <div class="pub-hero-panel">
            <div class="pub-hero-panel-title">
                <x-icon name="shield" size="20" />
                Transparent Process
            </div>
            <div class="pub-steps">
                <div class="pub-step-item">
                    <div class="pub-step-num">1</div>
                    <div>
                        <div class="pub-step-label">Donation Verification</div>
                        <div class="pub-step-desc">Admins review condition, validity & quantities before publication.</div>
                    </div>
                </div>
                <div class="pub-step-item">
                    <div class="pub-step-num">2</div>
                    <div>
                        <div class="pub-step-label">Proof-backed Requests</div>
                        <div class="pub-step-desc">Requesters submit genuine certificates or proofs of urgency.</div>
                    </div>
                </div>
                <div class="pub-step-item">
                    <div class="pub-step-num">3</div>
                    <div>
                        <div class="pub-step-label">Secure Claim Codes</div>
                        <div class="pub-step-desc">Automated codes & deadlines guarantee items reach the right hands.</div>
                    </div>
                </div>
            </div>
            <div class="pub-hero-panel-footer">
                <a href="{{ route('how-it-works') }}" style="font-size:13px;font-weight:600;">
                    Learn full workflow &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Dynamic Platform Statistics --}}
    <div class="container">
        <div class="pub-stats-row">
            <div class="pub-stat">
                <div class="pub-stat-num">{{ number_format($stats['total_items_shared']) }}+</div>
                <div class="pub-stat-label">Physical Items Shared</div>
            </div>
            <div class="pub-stat">
                <div class="pub-stat-num">{{ number_format($stats['completed_claims']) }}</div>
                <div class="pub-stat-label">Verified Claims Completed</div>
            </div>
            <div class="pub-stat">
                <div class="pub-stat-num">{{ number_format($stats['active_members']) }}</div>
                <div class="pub-stat-label">Active Community Members</div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     HOW IT WORKS  (wireframe: 3-step cards below hero)
     ============================================================ --}}
<section class="pub-section" style="background: var(--surface-soft); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
        <div class="pub-section-header">
            <span class="status-pill status-info">How It Works</span>
            <h2>Simple. Transparent. Trusted.</h2>
            <p>Three steps from surplus to someone who needs it.</p>
        </div>
        <div class="pub-steps-grid">
            <div class="pub-card-step">
                <div class="pub-card-step-num">1</div>
                <div style="color:var(--brand-primary);margin-bottom:14px;"><x-icon name="gift" size="36" /></div>
                <h3>Post</h3>
                <p>Donors list a physical item with photos and details. Requesters post what they need with proof documentation.</p>
            </div>
            <div class="pub-card-step">
                <div class="pub-card-step-num">2</div>
                <div style="color:var(--brand-primary);margin-bottom:14px;"><x-icon name="shield" size="36" /></div>
                <h3>Admin Verifies</h3>
                <p>Every post is reviewed by an administrator before it goes live. Quality and legitimacy are confirmed.</p>
            </div>
            <div class="pub-card-step">
                <div class="pub-card-step-num">3</div>
                <div style="color:var(--brand-primary);margin-bottom:14px;"><x-icon name="check-circle" size="36" /></div>
                <h3>Match & Claim</h3>
                <p>Matched items are claimed through a secure code-verified pickup. No cash ever changes hands.</p>
            </div>
        </div>
        <div style="text-align:center;">
            <a href="{{ route('how-it-works') }}" class="btn btn-outline">See full workflow details &rarr;</a>
        </div>
    </div>
</section>

{{-- ============================================================
     CATEGORIES  (wireframe: grid of category cards)
     ============================================================ --}}
<section class="pub-section">
    <div class="container">
        <div class="pub-section-header">
            <span class="status-pill status-approved">Categories</span>
            <h2>What Can You Share?</h2>
            <p>We focus on high-impact physical resources categorised for rapid matching.</p>
        </div>

        <div class="grid-3">
            @foreach($categories as $category)
                <a href="{{ route('donations.index', ['category' => $category->id]) }}" class="pub-cat-card">
                    <div class="pub-cat-icon">
                        @switch($category->slug)
                            @case('food')           <x-icon name="utensils"  size="28" /> @break
                            @case('school-supplies')<x-icon name="book-open" size="28" /> @break
                            @case('clothing')       <x-icon name="shirt"     size="28" /> @break
                            @case('medical')        <x-icon name="activity"  size="28" /> @break
                            @case('household')      <x-icon name="home"      size="28" /> @break
                            @case('electronics')    <x-icon name="laptop"    size="28" /> @break
                            @default                <x-icon name="package"   size="28" />
                        @endswitch
                    </div>
                    <h3>{{ $category->name }}</h3>
                    <p>{{ Str::limit($category->description, 90) }}</p>
                    <div class="pub-cat-footer">
                        <span>Available items:</span>
                        <span class="status-pill status-approved">{{ $category->donations_count }} listings</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:32px;">
            <a href="{{ route('categories') }}" class="btn btn-outline">View All Categories &rarr;</a>
        </div>
    </div>
</section>

{{-- ============================================================
     APPROVED DONATIONS PREVIEW
     ============================================================ --}}
<section class="pub-section" style="background: var(--surface-soft); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
        <div class="pub-section-header-row">
            <div>
                <span class="status-pill status-approved" style="margin-bottom:8px;">Available Now</span>
                <h2>Approved Physical Donations</h2>
                <p>Verified resources waiting to be matched with beneficiaries.</p>
            </div>
            <a href="{{ route('donations.index') }}" class="btn btn-primary btn-sm">Browse All &rarr;</a>
        </div>

        @if($featuredDonations->isEmpty())
            <div class="card" style="text-align:center;padding:64px;">
                <div style="color:var(--text-muted);margin-bottom:16px;"><x-icon name="package" size="52" /></div>
                <p>No approved items currently listed. Be the first to post a resource!</p>
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-sm" style="margin-top:12px;">
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
                                <div style="color:var(--text-muted);opacity:0.5;">
                                    @switch($donation->category->slug ?? '')
                                        @case('food')           <x-icon name="utensils"  size="52" /> @break
                                        @case('school-supplies')<x-icon name="book-open" size="52" /> @break
                                        @case('clothing')       <x-icon name="shirt"     size="52" /> @break
                                        @case('medical')        <x-icon name="activity"  size="52" /> @break
                                        @default                <x-icon name="package"   size="52" />
                                    @endswitch
                                </div>
                            @endif
                            <span class="status-pill status-approved" style="position:absolute;top:12px;right:12px;box-shadow:var(--shadow-sm);">
                                {{ $donation->quantity_available }} {{ $donation->unit }} available
                            </span>
                        </div>
                        <div class="donation-card-body">
                            <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--brand-primary);margin-bottom:4px;">
                                {{ $donation->category->name ?? 'General' }}
                            </div>
                            <h3 class="donation-card-title">{{ Str::limit($donation->title, 55) }}</h3>
                            <div class="donation-meta">
                                <span><x-icon name="map-pin" size="13" /> {{ $donation->pickup_city ?? 'Makati' }}</span>
                                <span><x-icon name="check"   size="13" /> {{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</span>
                            </div>
                            <p style="font-size:13px;color:var(--text-soft);margin-bottom:16px;">{{ Str::limit($donation->description, 85) }}</p>
                            <div class="donation-card-footer">
                                <span style="font-size:12px;color:var(--text-muted);">By {{ $donation->donor->name ?? 'Verified Donor' }}</span>
                                <a href="{{ route('donations.show', $donation->id) }}" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============================================================
     CTA BANNER
     ============================================================ --}}
<section class="pub-section">
    <div class="container">
        <div class="pub-cta-banner">
            <div>
                <h2>Do You Have Surplus Physical Items to Share?</h2>
                <p>Whether you're an individual with books and uniforms, or an organisation with excess inventory — your surplus can directly uplift a community.</p>
                <div class="pub-cta-btns">
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
