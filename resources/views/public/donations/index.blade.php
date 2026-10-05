@extends('layouts.public')

@section('title', 'Browse Available Donations')

@section('content')
<div class="container" style="padding: 40px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <span class="status-pill status-approved" style="margin-bottom: 6px;">Resource Registry</span>
            <h1>Browse Available Donations</h1>
            <p>Verified physical items ready for community allocation and beneficiary claiming.</p>
        </div>
        <a href="{{ route('donations.create') }}" class="btn btn-accent">
            🎁 + Post a Donation
        </a>
    </div>

    <!-- Search & Filter Card -->
    <div class="card" style="margin-bottom: 32px; padding: 20px;">
        <form action="{{ route('donations.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 2; min-width: 220px;">
                <label for="search">Keyword Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search items, titles, cities...">
            </div>

            <div style="flex: 1; min-width: 180px;">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="condition">Condition</label>
                <select id="condition" name="condition">
                    <option value="">Any Condition</option>
                    <option value="new" {{ request('condition') == 'new' ? 'selected' : '' }}>Brand New</option>
                    <option value="like_new" {{ request('condition') == 'like_new' ? 'selected' : '' }}>Like New</option>
                    <option value="good" {{ request('condition') == 'good' ? 'selected' : '' }}>Good Condition</option>
                    <option value="fair" {{ request('condition') == 'fair' ? 'selected' : '' }}>Fair</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('donations.index') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    @if($donations->isEmpty())
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">📦</div>
            <h3>No Available Donations Found</h3>
            <p>We couldn't find any approved donations matching your search criteria.</p>
            <a href="{{ route('donations.index') }}" class="btn btn-outline btn-sm" style="margin-top: 12px;">Clear Filters</a>
        </div>
    @else
        <div class="grid-3" style="margin-bottom: 36px;">
            @foreach($donations as $donation)
                <div class="donation-card">
                    <div class="donation-card-img">
                        @if($donation->images->isNotEmpty())
                            <img src="{{ $donation->images->first()->url }}" alt="{{ $donation->title }}">
                        @else
                            <div style="font-size: 48px; opacity: 0.6;">
                                @switch($donation->category->slug ?? '')
                                    @case('food') 🥫 @break
                                    @case('school-supplies') 🎒 @break
                                    @case('clothing') 👕 @break
                                    @case('medical') 🩺 @break
                                    @default 📦
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
                            <span>📍 {{ $donation->pickup_city ?? 'Makati City' }}</span>
                            <span>✨ {{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</span>
                            @if($donation->expiration_date)
                                <span>⏳ Exp: {{ $donation->expiration_date->format('M d, Y') }}</span>
                            @endif
                        </div>

                        <p style="font-size: 13px; color: var(--text-soft); margin-bottom: 16px;">
                            {{ Str::limit($donation->description, 85) }}
                        </p>

                        <div class="donation-card-footer">
                            <span style="font-size: 12px; color: var(--text-muted);">
                                Total: {{ $donation->quantity_total }} {{ $donation->unit }}
                            </span>
                            <a href="{{ route('donations.show', $donation->id) }}" class="btn btn-primary btn-sm">
                                View Details &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            {{ $donations->links() }}
        </div>
    @endif
</div>
@endsection
