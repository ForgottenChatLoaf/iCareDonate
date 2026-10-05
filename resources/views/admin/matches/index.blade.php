@extends('layouts.admin')

@section('title', 'Admin — Smart Resource Matching & Stock Allocation')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-verified" style="margin-bottom: 6px;">
        <x-icon name="repeat" size="14" /> Allocation Engine
    </span>
    <h1>Resource Matching &amp; Stock Allocation</h1>
    <p>Pair verified beneficiary requests directly with approved physical donation inventory. Issue secure claim codes and dispatch schedules.</p>
</div>

<!-- Match Creation Form Card -->
<div class="card" style="padding: 28px; margin-bottom: 32px; border: 2px solid var(--brand-primary); background: var(--surface);">
    <h3 style="color: var(--brand-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <x-icon name="repeat" size="20" /> Allocate Stock &amp; Generate Transaction
    </h3>

    <form action="{{ route('admin.matches.create') }}" method="POST">
        @csrf

        <div class="form-row" style="margin-bottom: 16px;">
            <div class="form-group" style="flex: 1;">
                <label for="request_id">Select Beneficiary Request <span style="color:var(--danger)">*</span></label>
                <select id="request_id" name="request_id" required>
                    <option value="">-- Choose Approved Request --</option>
                    @foreach($requests as $req)
                        @php $unmatched = $req->quantity_requested - $req->quantity_matched; @endphp
                        <option value="{{ $req->id }}">
                            #{{ $req->id }} — {{ $req->item_name }} (Needs: {{ $unmatched }}) &middot; {{ $req->requester->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="flex: 1;">
                <label for="donation_id">Select Available Donation Inventory <span style="color:var(--danger)">*</span></label>
                <select id="donation_id" name="donation_id" required>
                    <option value="">-- Choose Available Donation --</option>
                    @foreach($donations as $don)
                        <option value="{{ $don->id }}">
                            #{{ $don->id }} — {{ $don->title }} (Available: {{ $don->quantity_available }} {{ $don->unit }}) &middot; {{ $don->donor->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="max-width: 140px;">
                <label for="quantity">Quantity to Allocate <span style="color:var(--danger)">*</span></label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required>
            </div>
        </div>

        <div class="form-row" style="margin-bottom: 16px;">
            <div class="form-group">
                <label for="pickup_date">Collection / Handoff Date <span style="color:var(--danger)">*</span></label>
                <input type="date" id="pickup_date" name="pickup_date" value="{{ date('Y-m-d', strtotime('+2 days')) }}" required>
            </div>

            <div class="form-group">
                <label for="pickup_time">Pickup Window / Time <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pickup_time" name="pickup_time" value="10:00 AM - 2:00 PM" required>
            </div>

            <div class="form-group">
                <label for="claim_deadline_days">Claim Expiration Window</label>
                <select id="claim_deadline_days" name="claim_deadline_days">
                    <option value="3">3 Days from pickup date</option>
                    <option value="5" selected>5 Days from pickup date</option>
                    <option value="7">7 Days from pickup date</option>
                    <option value="14">14 Days (Extended)</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="pickup_address">Physical Collection Center / Dispatch Location <span style="color:var(--danger)">*</span></label>
            <input type="text" id="pickup_address" name="pickup_address" value="Makati Community Logistics Hub, Ground Floor, Bel-Air, Makati" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
            <x-icon name="check-circle" size="18" /> Execute Match &amp; Generate Claim Code
        </button>
    </form>
</div>

<!-- Recent Matches & Transactions -->
<div class="card">
    <div class="card-header">
        <h3>Active Allocations &amp; Claim Slips</h3>
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline btn-sm">All Transactions &rarr;</a>
    </div>

    @if($recentMatches->isEmpty())
        <div style="text-align: center; padding: 36px 0; color: var(--text-muted);">
            <x-icon name="repeat" size="36" style="margin-bottom: 8px;" />
            <p>No active matches created yet. Select an approved request and available donation above.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Match ID</th>
                        <th>Donation Source</th>
                        <th>Beneficiary Request</th>
                        <th>Allocated Qty</th>
                        <th>Claim Code</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentMatches as $m)
                        <tr>
                            <td>#{{ $m->id }}</td>
                            <td>
                                <div style="font-weight: 700;">{{ Str::limit($m->donation->title, 28) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Donor: {{ $m->donation->donor->name }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ Str::limit($m->request->item_name, 28) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Req: {{ $m->request->requester->name }}</div>
                            </td>
                            <td><strong>{{ $m->quantity }}</strong> items</td>
                            <td>
                                @if($m->transaction)
                                    <strong style="font-family: monospace; color: var(--brand-primary);">{{ $m->transaction->claim_code }}</strong>
                                @else
                                    <span style="color: var(--text-muted);">Pending</span>
                                @endif
                            </td>
                            <td>{{ $m->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($m->transaction)
                                    <a href="{{ route('admin.transactions.show', $m->transaction->id) }}" class="btn btn-outline btn-sm">
                                        View Slip &rarr;
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
