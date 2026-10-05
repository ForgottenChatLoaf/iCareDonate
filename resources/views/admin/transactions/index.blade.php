@extends('layouts.admin')

@section('title', 'Admin — Transactions & Claims Monitoring')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-info" style="margin-bottom: 6px;">
            <x-icon name="ticket" size="14" /> Platform Handoffs
        </span>
        <h1>Transactions &amp; Claim Slips</h1>
        <p>Monitor real-time pickup confirmations, active claim codes, and completion status.</p>
    </div>
    <a href="{{ route('admin.matches.index') }}" class="btn btn-primary">
        <x-icon name="repeat" size="16" /> + Create New Allocation
    </a>
</div>

<!-- Search & Status Filter -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <form action="{{ route('admin.transactions.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
        <div style="flex: 2; min-width: 220px;">
            <label for="search">Search Claim Code or Participant</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="CLAIM-XXXXXX, donor or requester name...">
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label for="status">Transaction Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="pending_claim" {{ request('status') == 'pending_claim' ? 'selected' : '' }}>Pending Claim</option>
                <option value="claimed" {{ request('status') == 'claimed' ? 'selected' : '' }}>Claim Acknowledged</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    @if($transactions->isEmpty())
        <div style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
            <x-icon name="ticket" size="40" style="margin-bottom: 8px;" />
            <p>No transactions match your search criteria.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Claim Code</th>
                        <th>Resource Item</th>
                        <th>Qty</th>
                        <th>Donor</th>
                        <th>Requester</th>
                        <th>Pickup Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr>
                            <td>
                                <strong style="font-family: monospace; color: var(--brand-primary); font-size: 15px;">
                                    {{ $tx->claim_code }}
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ Str::limit($tx->donation->title, 28) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $tx->donation->category->name }}</div>
                            </td>
                            <td><strong>{{ $tx->quantity }}</strong> {{ $tx->donation->unit }}</td>
                            <td>{{ $tx->donor->name }}</td>
                            <td>{{ $tx->requester->name }}</td>
                            <td>{{ $tx->pickup_date ? $tx->pickup_date->format('M d, Y') : 'Scheduled' }}</td>
                            <td>
                                <span class="status-pill status-{{ $tx->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $tx->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.transactions.show', $tx->id) }}" class="btn btn-outline btn-sm">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
