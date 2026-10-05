@extends('layouts.user')

@section('title', 'Request Status — ' . $resourceRequest->item_name)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $resourceRequest->status }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $resourceRequest->status)) }}
            </span>
            <h1>Resource Request Tracker</h1>
            <p>Application #{{ $resourceRequest->id }} &middot; Submitted {{ $resourceRequest->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <div>
            <a href="{{ route('user.requests') }}" class="btn btn-outline">My Requests List &rarr;</a>
        </div>
    </div>

    <!-- Status Progress Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 24px;">
        <h3 style="font-size: 15px; margin-bottom: 16px; color: var(--brand-primary);">Verification &amp; Allocation Progress</h3>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; text-align: center;">
            <div style="padding: 12px; background: {{ in_array($resourceRequest->status, ['submitted', 'proof_review', 'approved', 'matched', 'claimed', 'completed']) ? 'var(--brand-primary-light)' : 'var(--surface-soft)' }}; border-radius: var(--radius-md);">
                <div style="font-weight: 700; font-size: 13px;">1. Submitted</div>
                <div style="font-size: 11px; color: var(--text-muted);">Application sent</div>
            </div>

            <div style="padding: 12px; background: {{ in_array($resourceRequest->status, ['proof_review', 'approved', 'matched', 'claimed', 'completed']) ? 'var(--brand-primary-light)' : 'var(--surface-soft)' }}; border-radius: var(--radius-md);">
                <div style="font-weight: 700; font-size: 13px;">2. Proof Review</div>
                <div style="font-size: 11px; color: var(--text-muted);">Admin assessing</div>
            </div>

            <div style="padding: 12px; background: {{ in_array($resourceRequest->status, ['approved', 'matched', 'claimed', 'completed']) ? 'var(--brand-primary-light)' : 'var(--surface-soft)' }}; border-radius: var(--radius-md);">
                <div style="font-weight: 700; font-size: 13px;">3. Matched</div>
                <div style="font-size: 11px; color: var(--text-muted);">Stock allocated</div>
            </div>

            <div style="padding: 12px; background: {{ $resourceRequest->status === 'completed' ? 'var(--success-bg)' : 'var(--surface-soft)' }}; border-radius: var(--radius-md);">
                <div style="font-weight: 700; font-size: 13px;">4. Claimed</div>
                <div style="font-size: 11px; color: var(--text-muted);">Handoff completed</div>
            </div>
        </div>
    </div>

    <!-- Active Transactions / Claim Slips -->
    @if($resourceRequest->transactions->isNotEmpty())
        <div class="card" style="margin-bottom: 24px; border: 2px solid var(--brand-primary); background: var(--surface-soft);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="color: var(--brand-primary); margin: 0;"><x-icon name="check-circle" size="20" /> Matched Resource Allocation Ready!</h3>
                <span class="status-pill status-approved">Ready for Pickup</span>
            </div>
            <p>Your request has been matched with physical items. Present your official claim code at the designated location.</p>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
                @foreach($resourceRequest->transactions as $tx)
                    <div style="background: var(--surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Claim Code:</div>
                            <div style="font-size: 22px; font-weight: 700; color: var(--brand-primary); font-family: monospace;">{{ $tx->claim_code }}</div>
                            <div style="font-size: 13px; color: var(--text-soft); margin-top: 4px;">
                                Quantity: <strong>{{ $tx->quantity }}</strong> &middot; Pickup Address: {{ $tx->pickup_address ?? 'Pickup center' }}
                            </div>
                        </div>
                        <a href="{{ route('claims.show', $tx->id) }}" class="btn btn-accent">
                            Open Official Claim Slip &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Request Details Card -->
    <div class="card" style="padding: 28px; margin-bottom: 24px;">
        <h3 style="margin-bottom: 16px;">Application Details</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted); display: block;">Requested Item:</span>
                <strong>{{ $resourceRequest->item_name }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Quantity Requested:</span>
                <strong>{{ $resourceRequest->quantity_requested }}</strong> (Matched: {{ $resourceRequest->quantity_matched }})
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Urgency Priority:</span>
                <span class="status-pill status-{{ $resourceRequest->urgency_level === 'emergency' || $resourceRequest->urgency_level === 'high' ? 'rejected' : 'info' }}">
                    {{ ucfirst($resourceRequest->urgency_level) }}
                </span>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block;">Delivery / Contact:</span>
                <strong>{{ $resourceRequest->contact_number }}</strong>
            </div>
        </div>

        <h4 style="font-size: 14px; margin-bottom: 6px;">Stated Reason &amp; Need:</h4>
        <p style="white-space: pre-line; background: var(--surface-soft); padding: 14px; border-radius: var(--radius-md); font-size: 14px;">
            {{ $resourceRequest->purpose }}
        </p>

        @if($resourceRequest->rejection_reason)
            <div style="margin-top: 16px; padding: 14px; background: var(--danger-bg); border-radius: var(--radius-md); border-left: 4px solid var(--danger);">
                <strong style="color: var(--danger);">Application Decision Note:</strong>
                <p style="margin: 4px 0 0; color: var(--danger); font-size: 13px;">{{ $resourceRequest->rejection_reason }}</p>
            </div>
        @endif

        <!-- Proof Attachments -->
        <h4 style="font-size: 14px; margin: 20px 0 10px;">Attached Supporting Proofs:</h4>
        @if($resourceRequest->proofs->isEmpty())
            <p style="font-size: 13px; color: var(--text-muted);">No documents were attached to this application.</p>
        @else
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                @foreach($resourceRequest->proofs as $proof)
                    <div style="border: 1px solid var(--border); padding: 10px 14px; border-radius: var(--radius-md); background: var(--surface-soft); font-size: 13px; display: flex; align-items: center; gap: 8px;">
                        <x-icon name="file-text" size="14" />
                        <a href="{{ $proof->url }}" target="_blank" style="font-weight: 600;">
                            {{ Str::limit($proof->file_name, 25) }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if(!in_array($resourceRequest->status, ['completed', 'claimed', 'cancelled']))
        <div style="text-align: right;">
            <form action="{{ route('requests.cancel', $resourceRequest->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">
                    Cancel This Request
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
