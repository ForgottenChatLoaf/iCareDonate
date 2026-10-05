@extends('layouts.public')

@section('title', $donation->title)

@section('content')
<div class="container" style="padding: 40px 20px;">
    <!-- Breadcrumbs -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
        <a href="{{ route('home') }}">Home</a> &rsaquo; 
        <a href="{{ route('donations.index') }}">Donations</a> &rsaquo; 
        <a href="{{ route('donations.index', ['category' => $donation->category_id]) }}">{{ $donation->category->name }}</a> &rsaquo; 
        <span>{{ Str::limit($donation->title, 40) }}</span>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; align-items: start;">
        <!-- Left: Image & Description -->
        <div>
            <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
                <div style="height: 380px; background: var(--surface-soft); display: flex; align-items: center; justify-content: center; position: relative;">
                    @if($donation->images->isNotEmpty())
                        <img id="main-donation-image" src="{{ $donation->images->first()->url }}" alt="{{ $donation->title }}" style="width: 100%; height: 100%; object-fit: contain;">
                    @else
                        <div style="font-size: 80px; opacity: 0.5;">
                        @switch($donation->category->slug ?? '')
                                @case('food')           <x-icon name="utensils"  size="80" /> @break
                                @case('school-supplies')<x-icon name="book-open" size="80" /> @break
                                @case('clothing')       <x-icon name="shirt"     size="80" /> @break
                                @case('medical')        <x-icon name="activity"  size="80" /> @break
                                @default                <x-icon name="package"   size="80" />
                            @endswitch
                        </div>
                    @endif
                    <span class="status-pill status-approved" style="position: absolute; top: 16px; left: 16px;">
                        {{ $donation->quantity_available }} {{ $donation->unit }} Available
                    </span>
                </div>

                @if($donation->images->count() > 1)
                    <div style="display: flex; gap: 8px; padding: 12px; background: var(--surface); border-top: 1px solid var(--border);">
                        @foreach($donation->images as $img)
                            <img src="{{ $img->url }}" onclick="document.getElementById('main-donation-image').src='{{ $img->url }}'" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--border); cursor: pointer;" alt="Thumbnail">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card">
                <h3 style="color: var(--brand-primary); margin-bottom: 12px;">Detailed Item Description</h3>
                <p style="font-size: 15px; line-height: 1.7; white-space: pre-line; color: var(--text);">
                    {{ $donation->description }}
                </p>

                @if($donation->pickup_notes)
                    <div style="margin-top: 24px; padding: 16px; background: var(--surface-soft); border-radius: var(--radius-md); border-left: 4px solid var(--brand-primary);">
                        <h4 style="font-size: 14px; color: var(--brand-primary); margin-bottom: 6px;">Pickup &amp; Handling Instructions:</h4>
                        <p style="font-size: 13px; margin: 0; color: var(--text-soft);">{{ $donation->pickup_notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Action & Specification Details -->
        <div>
            <div class="card" style="padding: 28px; margin-bottom: 24px; border: 2px solid var(--brand-primary-light);">
                <div style="font-size: 12px; text-transform: uppercase; font-weight: 700; color: var(--brand-primary); margin-bottom: 6px;">
                    {{ $donation->category->name }}
                </div>
                <h2 style="font-size: 1.8rem; margin-bottom: 16px;">{{ $donation->title }}</h2>

                <!-- Inventory breakdown table -->
                <div style="background: var(--surface-soft); padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                        <span>Currently Available:</span>
                        <strong style="color: var(--success); font-size: 16px;">{{ $donation->quantity_available }} {{ $donation->unit }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: var(--text-muted);">
                        <span>Reserved in Active Claims:</span>
                        <span>{{ $donation->quantity_reserved }} {{ $donation->unit }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted);">
                        <span>Total Quantity Offered:</span>
                        <span>{{ $donation->quantity_total }} {{ $donation->unit }}</span>
                    </div>
                </div>

                @if($donation->quantity_available > 0)
                    <a href="{{ route('requests.create', ['donation_id' => $donation->id]) }}" class="btn btn-accent btn-lg" style="width: 100%; margin-bottom: 12px;">
                        <x-icon name="file-text" size="16" /> Request This Resource
                    </a>
                    <p style="font-size: 12px; text-align: center; color: var(--text-muted); margin: 0;">
                        Requires supporting certificate or proof of need during application.
                    </p>
                @else
                    <button class="btn btn-outline" style="width: 100%; opacity: 0.7; cursor: not-allowed;" disabled>
                        Fully Allocated / In Process
                    </button>
                    <p style="font-size: 12px; text-align: center; color: var(--warning); margin-top: 8px;">
                        All available items are currently matched or claimed.
                    </p>
                @endif
            </div>

            <!-- Specifications Card -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Item Specifications</h3>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                        <span style="color: var(--text-muted);">Physical Condition:</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</strong>
                    </div>

                    @if($donation->expiration_date)
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                            <span style="color: var(--text-muted);">Expiration Date:</span>
                            <strong style="color: var(--warning);">{{ $donation->expiration_date->format('M d, Y') }}</strong>
                        </div>
                    @endif

                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                        <span style="color: var(--text-muted);">Delivery / Handover:</span>
                        <strong>{{ ucfirst($donation->pickup_method) }}</strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                        <span style="color: var(--text-muted);">Location / City:</span>
                        <strong>{{ $donation->pickup_city ?? 'Makati City' }}, {{ $donation->pickup_province ?? 'Metro Manila' }}</strong>
                    </div>

                    @if($donation->pickup_schedule)
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                            <span style="color: var(--text-muted);">Available Schedule:</span>
                            <strong>{{ $donation->pickup_schedule }}</strong>
                        </div>
                    @endif

                    <div style="display: flex; justify-content: space-between; padding-bottom: 4px;">
                        <span style="color: var(--text-muted);">Verified By Admin:</span>
                        <strong style="color: var(--brand-primary);">Yes &middot; Safety Checked</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
