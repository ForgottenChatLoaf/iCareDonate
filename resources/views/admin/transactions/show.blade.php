@extends('layouts.admin')

@section('title', 'Admin — Transaction #' . $transaction->id . ' (' . $transaction->claim_code . ')')

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $transaction->status }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}
            </span>
            <h1>Transaction #{{ $transaction->id }}</h1>
            <p>Claim Code: <strong style="font-family: monospace; color: var(--brand-primary);">{{ $transaction->claim_code }}</strong> &middot; Generated {{ $transaction->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline">&larr; Back to Transactions</a>
    </div>

    <!-- Claim Summary Card -->
    <div class="card" style="padding: 32px; margin-bottom: 24px;">
        <div style="text-align: center; background: var(--surface-soft); padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; border: 1px dashed var(--brand-primary);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Official Claim Code</div>
            <div style="font-size: 34px; font-family: monospace; font-weight: 800; color: var(--brand-primary); margin: 6px 0;">
                {{ $transaction->claim_code }}
            </div>
            @if($transaction->claim_deadline)
                <div style="font-size: 13px; color: var(--warning); font-weight: 600;">
                    Deadline: {{ $transaction->claim_deadline->format('M d, Y') }}
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 13px; margin-bottom: 24px;">
            <div style="background: var(--surface-soft); padding: 16px; border-radius: var(--radius-md);">
                <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Donation Source:</span>
                <strong style="font-size: 15px; color: var(--text);">{{ $transaction->donation->title }}</strong>
                <div style="margin-top: 6px;">Donor: <strong>{{ $transaction->donor->name }}</strong> ({{ $transaction->donor->phone }})</div>
            </div>

            <div style="background: var(--surface-soft); padding: 16px; border-radius: var(--radius-md);">
                <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Beneficiary Requester:</span>
                <strong style="font-size: 15px; color: var(--text);">{{ $transaction->requester->name }}</strong>
                <div style="margin-top: 6px;">Contact: {{ $transaction->requester->phone }} &middot; {{ $transaction->requester->email }}</div>
            </div>

            <div style="grid-column: span 2; background: var(--surface-soft); padding: 16px; border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span>Allocated Quantity:</span>
                    <strong style="font-size: 16px; color: var(--brand-primary);">{{ $transaction->quantity }} {{ $transaction->donation->unit }}</strong>
                </div>
                <div>Pickup Address: <strong>{{ $transaction->pickup_address }}</strong></div>
                <div style="margin-top: 4px; color: var(--text-soft);">
                    Collection Date: <strong>{{ $transaction->pickup_date ? $transaction->pickup_date->format('M d, Y') : 'Scheduled' }}</strong> ({{ $transaction->pickup_time }})
                </div>
            </div>
        </div>

        <!-- Management Actions -->
        @if(in_array($transaction->status, ['pending_claim', 'claimed']))
            <div style="display: flex; gap: 14px; flex-wrap: wrap; border-top: 1px solid var(--border); padding-top: 20px;">
                <form action="{{ route('admin.transactions.complete', $transaction->id) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Confirm that items were physically transferred to the requester?');">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                        <x-icon name="check-circle" size="16" /> Mark Transaction Completed
                    </button>
                </form>

                <div style="flex: 1;">
                    <button type="button" class="btn btn-outline" style="width: 100%; color: var(--danger); border-color: var(--danger);" onclick="document.getElementById('cancel-box').style.display = document.getElementById('cancel-box').style.display === 'none' ? 'block' : 'none'">
                        Cancel Transaction &amp; Revert Stock
                    </button>
                    <div id="cancel-box" style="display: none; background: var(--danger-bg); padding: 14px; border-radius: var(--radius-md); margin-top: 10px;">
                        <form action="{{ route('admin.transactions.cancel', $transaction->id) }}" method="POST">
                            @csrf
                            <label for="cancel_reason" style="font-size: 12px; color: var(--danger);">Reason for Cancellation <span style="color:var(--danger)">*</span></label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="2" required placeholder="e.g. Requester missed pickup deadline or declined resource." style="font-size: 13px;"></textarea>
                            <button type="submit" class="btn btn-danger btn-sm" style="margin-top: 8px; width: 100%;">Confirm Cancellation</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 14px; background: var(--surface-soft); border-radius: var(--radius-md); font-weight: 600;">
                Transaction finalized with status: <span class="status-pill status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
            </div>
        @endif
    </div>
</div>
@endsection
