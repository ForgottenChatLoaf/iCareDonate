@extends('layouts.user')

@section('title', 'Donation Offer Preview — ' . $donation->title)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $donation->status }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $donation->status)) }}
            </span>
            <h1>Donation Offer Review</h1>
            <p>Verification preview of your donation submission.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('donations.edit', $donation->id) }}" class="btn btn-outline">
                <x-icon name="edit" size="14" /> Edit Details
            </a>
            <a href="{{ route('user.donations') }}" class="btn btn-primary">My Donations List &rarr;</a>
        </div>
    </div>

    @if($donation->status === 'pending_verification')
        <div class="alert alert-warning" style="margin-bottom: 24px;">
            <span><x-icon name="clock" size="16" /> Your donation is currently in the <strong>Admin Verification Queue</strong>. Once safety, authenticity, and inventory guidelines are validated, it will appear publicly on the Browse page.</span>
        </div>
    @elseif($donation->status === 'needs_information')
        <div class="alert alert-info" style="margin-bottom: 24px;">
            <span><x-icon name="alert" size="16" /> <strong>Additional Information Requested:</strong> {{ $donation->info_request }}</span>
        </div>
    @endif

    <div class="card" style="padding: 32px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <span class="status-pill status-info" style="margin-bottom: 8px;">{{ $donation->category->name }}</span>
                <h2>{{ $donation->title }}</h2>
                <div style="font-size: 13px; color: var(--text-muted);">
                    Submitted on {{ $donation->submitted_at ? $donation->submitted_at->format('M d, Y h:i A') : 'Recently' }}
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 24px; font-weight: 700; color: var(--brand-primary);">
                    {{ $donation->quantity_total }} {{ $donation->unit }}
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">Offered Quantity</div>
            </div>
        </div>

        @if($donation->images->isNotEmpty())
            <div style="display: flex; gap: 12px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px;">
                @foreach($donation->images as $img)
                    <img src="{{ $img->url }}" alt="Donation Image" style="height: 140px; border-radius: var(--radius-md); border: 1px solid var(--border); object-fit: cover;">
                @endforeach
            </div>
        @endif

        <h3 style="font-size: 16px; margin-bottom: 8px;">Item Description</h3>
        <p style="white-space: pre-line; margin-bottom: 24px;">{{ $donation->description }}</p>

        <h3 style="font-size: 16px; margin-bottom: 12px;">Logistics &amp; Verification Details</h3>
        <div style="background: var(--surface-soft); padding: 18px; border-radius: var(--radius-md); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted); display: block;">Physical Condition:</span>
                <strong>{{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Handover Method:</span>
                <strong>{{ ucfirst($donation->pickup_method) }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Pickup City:</span>
                <strong>{{ $donation->pickup_city ?? 'Makati' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Pickup Schedule:</span>
                <strong>{{ $donation->pickup_schedule ?? 'Flexible coordination' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Contact Person:</span>
                <strong>{{ $donation->donor_name }} ({{ $donation->contact_number }})</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Pickup Address:</span>
                <strong>{{ $donation->pickup_address }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
