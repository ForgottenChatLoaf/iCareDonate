@extends('layouts.user')

@section('title', 'Official Claim Slip — ' . $transaction->claim_code)

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <span class="status-pill status-approved" style="margin-bottom: 6px;">
                <x-icon name="check-circle" size="14" /> Verified Allocation
            </span>
            <h1>Resource Claim Slip</h1>
            <p>Present this claim code at the designated collection location.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">
                <x-icon name="printer" size="14" /> Print Slip
            </button>
        </div>
    </div>

    <!-- Claim Slip Card -->
    <div class="card" style="padding: 36px; border: 2px dashed var(--brand-primary); background: var(--surface); box-shadow: var(--shadow-md); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid var(--border);">
            <div>
                <div style="font-family: var(--font-heading); font-size: 24px; color: var(--brand-primary); display: flex; align-items: center; gap: 8px;">
                    <x-icon name="heart" size="24" style="color: var(--brand-accent);" />
                    iCare<span>Donate</span>
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">Verified Non-Monetary Resource Transfer</div>
            </div>
            <div style="text-align: right;">
                <span class="status-pill status-{{ $transaction->status }}">
                    Status: {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}
                </span>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Tx #{{ $transaction->id }}</div>
            </div>
        </div>

        <!-- Prominent Claim Code Box -->
        <div style="text-align: center; margin: 32px 0; background: var(--surface-soft); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-strong);">
            <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                Official Claim Code
            </div>
            <div style="font-size: 38px; font-family: monospace; font-weight: 800; color: var(--brand-primary); letter-spacing: 0.12em;">
                {{ $transaction->claim_code }}
            </div>
            @if($transaction->claim_deadline)
                <div style="font-size: 12px; color: var(--warning); margin-top: 6px; font-weight: 600;">
                    <x-icon name="clock" size="14" /> Valid through: {{ $transaction->claim_deadline->format('M d, Y') }}
                </div>
            @endif
        </div>

        <!-- Item & Logistics Breakdown -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted); display: block;">Allocated Resource:</span>
                <strong style="font-size: 15px; color: var(--text);">{{ $transaction->donation->title }}</strong>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Quantity Granted:</span>
                <strong style="font-size: 16px; color: var(--brand-primary);">{{ $transaction->quantity }} {{ $transaction->donation->unit }}</strong>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Authorized Requester:</span>
                <strong>{{ $transaction->requester->name }}</strong>
                <div style="font-size: 12px; color: var(--text-muted);">{{ $transaction->requester->phone }}</div>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Contributing Donor:</span>
                <strong>{{ $transaction->donor->name }}</strong>
                <div style="font-size: 12px; color: var(--text-muted);">{{ $transaction->donor->phone }}</div>
            </div>

            <div style="grid-column: span 2; background: var(--surface-soft); padding: 14px; border-radius: var(--radius-md);">
                <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Designated Pickup Location &amp; Time:</span>
                <strong style="color: var(--text); font-size: 14px;">{{ $transaction->pickup_address ?? 'Contact donor/admin for central distribution desk' }}</strong>
                <div style="font-size: 12px; color: var(--text-soft); margin-top: 4px;">
                    <x-icon name="calendar" size="13" /> Scheduled: {{ $transaction->pickup_date ? $transaction->pickup_date->format('M d, Y') : 'Immediate' }} {{ $transaction->pickup_time ? 'at ' . $transaction->pickup_time : '' }}
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px;">
            @if($transaction->status === 'pending_claim' && auth()->id() === $transaction->requester_id)
                <form action="{{ route('claims.confirm', $transaction->id) }}" method="POST" style="flex: 1;">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <x-icon name="check-circle" size="16" /> Confirm Pickup Intention
                    </button>
                </form>
            @endif

            @if(in_array($transaction->status, ['pending_claim', 'claimed']) && (auth()->id() === $transaction->donor_id || auth()->user()->isAdmin()))
                <form action="{{ route('claims.complete', $transaction->id) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Confirm that items were physically transferred to the requester?');">
                    @csrf
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <x-icon name="check" size="16" /> Mark Handoff Completed &amp; Received
                    </button>
                </form>
            @endif

            @if($transaction->status === 'completed')
                <div style="width: 100%; text-align: center; padding: 12px; background: var(--success-bg); color: var(--success); border-radius: var(--radius-md); font-weight: 600;">
                    <x-icon name="check-circle" size="16" /> This transaction was successfully completed on {{ $transaction->completed_at ? $transaction->completed_at->format('M d, Y') : 'record' }}.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
