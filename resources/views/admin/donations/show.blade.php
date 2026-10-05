@extends('layouts.admin')

@section('title', 'Admin — Review Donation #' . $donation->id)

@section('content')
<div style="max-width: 960px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $donation->status }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $donation->status)) }}
            </span>
            <h1>Review Donation #{{ $donation->id }}</h1>
            <p>{{ $donation->title }} &middot; Submitted by {{ $donation->donor->name }}</p>
        </div>
        <a href="{{ route('admin.donations.index') }}" class="btn btn-outline">&larr; Back to Queue</a>
    </div>

    @if($donation->status === 'approved')
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <span><x-icon name="check-circle" size="16" /> This donation was verified and approved by <strong>{{ $donation->approver->name ?? 'Admin' }}</strong> on {{ $donation->approved_at ? $donation->approved_at->format('M d, Y h:i A') : 'record' }}.</span>
        </div>
    @elseif($donation->status === 'needs_information')
        <div class="alert alert-warning" style="margin-bottom: 24px;">
            <span><x-icon name="alert" size="16" /> Awaiting donor response. Information requested: <em>{{ $donation->info_request }}</em></span>
        </div>
    @elseif($donation->status === 'rejected')
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <span><x-icon name="alert" size="16" /> Donation rejected. Reason recorded: <em>{{ $donation->rejection_reason }}</em></span>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 24px;">
        <!-- Left: Details & Photos -->
        <div>
            <div class="card" style="margin-bottom: 24px;">
                <h3 style="color: var(--brand-primary); margin-bottom: 12px;">Item Specifications</h3>
                
                @if($donation->images->isNotEmpty())
                    <div style="margin-bottom: 16px; border-radius: var(--radius-md); overflow: hidden; max-height: 320px; background: var(--surface-soft); display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $donation->images->first()->url }}" alt="Donation Photo" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                @endif

                <p style="white-space: pre-line; line-height: 1.7; font-size: 14px;">{{ $donation->description }}</p>

                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border); font-size: 13px;">
                    <div><strong>Handover Method:</strong> {{ ucfirst($donation->pickup_method) }}</div>
                    <div style="margin-top: 4px;"><strong>Pickup Address:</strong> {{ $donation->pickup_address }} ({{ $donation->pickup_city ?? 'Makati' }})</div>
                    @if($donation->pickup_notes)
                        <div style="margin-top: 4px; color: var(--text-soft);"><strong>Special Notes:</strong> {{ $donation->pickup_notes }}</div>
                    @endif
                </div>
            </div>

            <!-- Inspection Checklist -->
            <div class="card" style="background: var(--surface-soft); border-left: 4px solid var(--brand-primary);">
                <h3 style="font-size: 15px; margin-bottom: 10px; color: var(--brand-primary);">Admin Verification Checklist</h3>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" checked style="width: auto;"> Physical item verified (strictly non-cash)
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" checked style="width: auto;"> Condition fits acceptable safety standards
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" checked style="width: auto;"> Quantity units and packaging validated
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" checked style="width: auto;"> Donor contact details verified
                    </label>
                </div>
            </div>
        </div>

        <!-- Right: Action Panel -->
        <div>
            <!-- Decision Actions -->
            <div class="card" style="padding: 24px; margin-bottom: 24px; border: 2px solid var(--border-strong);">
                <h3 style="margin-bottom: 16px;">Administrative Decision</h3>

                @if($donation->status !== 'approved')
                    <form action="{{ route('admin.donations.approve', $donation->id) }}" method="POST" style="margin-bottom: 16px;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;" onclick="return confirm('Approve this donation and publish it to the community registry?');">
                            <x-icon name="check-circle" size="16" /> Approve &amp; Make Available
                        </button>
                    </form>
                @endif

                <!-- Request More Info Drawer Form -->
                <div style="margin-bottom: 16px; padding-top: 14px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-outline btn-sm" style="width: 100%; margin-bottom: 10px;" onclick="document.getElementById('info-form-box').style.display = document.getElementById('info-form-box').style.display === 'none' ? 'block' : 'none'">
                        💬 Request More Information
                    </button>
                    <div id="info-form-box" style="display: none; background: var(--surface-soft); padding: 14px; border-radius: var(--radius-md);">
                        <form action="{{ route('admin.donations.request-info', $donation->id) }}" method="POST">
                            @csrf
                            <label for="info_request" style="font-size: 12px;">What information is needed?</label>
                            <textarea id="info_request" name="info_request" rows="3" required placeholder="e.g. Please provide photos of expiration date on canned goods." style="font-size: 13px;"></textarea>
                            <button type="submit" class="btn btn-outline btn-sm" style="margin-top: 8px; width: 100%;">Send Info Request</button>
                        </form>
                    </div>
                </div>

                <!-- Reject Drawer Form -->
                <div style="padding-top: 14px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-outline btn-sm" style="width: 100%; color: var(--danger); border-color: var(--danger);" onclick="document.getElementById('reject-form-box').style.display = document.getElementById('reject-form-box').style.display === 'none' ? 'block' : 'none'">
                        ⚠️ Reject Donation Offer
                    </button>
                    <div id="reject-form-box" style="display: none; background: var(--danger-bg); padding: 14px; border-radius: var(--radius-md); margin-top: 10px;">
                        <form action="{{ route('admin.donations.reject', $donation->id) }}" method="POST">
                            @csrf
                            <label for="rejection_reason" style="font-size: 12px; color: var(--danger);">Reason for Rejection <span style="color:var(--danger)">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="e.g. Items are expired, damaged, or violate physical safety policies." style="font-size: 13px;"></textarea>
                            <button type="submit" class="btn btn-danger btn-sm" style="margin-top: 8px; width: 100%;">Confirm Rejection</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Donor Contact Info -->
            <div class="card" style="padding: 20px;">
                <h4 style="font-size: 14px; margin-bottom: 12px;">Donor Contact Details</h4>
                <div style="font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
                    <div><strong>Name:</strong> {{ $donation->donor_name ?? $donation->donor->name }}</div>
                    <div><strong>Email:</strong> {{ $donation->donor->email }}</div>
                    <div><strong>Phone:</strong> {{ $donation->contact_number ?? $donation->donor->phone }}</div>
                    <div><strong>City:</strong> {{ $donation->pickup_city ?? 'Makati' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
