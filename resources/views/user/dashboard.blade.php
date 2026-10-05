@extends('layouts.user')

@section('title', 'Member Dashboard')

@section('content')
<div style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="status-pill status-approved" style="margin-bottom: 6px;">
                <x-icon name="check-circle" size="14" /> Community Member
            </span>
            <h1>Welcome, {{ auth()->user()->name }}</h1>
            <p>Here is your real-time resource sharing and assistance summary.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('donations.create') }}" class="btn btn-accent">
                <x-icon name="gift" size="16" /> + Post Donation
            </a>
            <a href="{{ route('donations.index') }}" class="btn btn-primary">
                <x-icon name="search" size="16" /> Browse Items
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid-4" style="margin-bottom: 32px;">
    <div class="card" style="border-left: 4px solid var(--brand-primary);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">My Posted Donations</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--brand-primary);">
            {{ $stats['my_donations'] }}
        </div>
        <a href="{{ route('user.donations') }}" style="font-size: 12px; font-weight: 600;">View all donations &rarr;</a>
    </div>

    <div class="card" style="border-left: 4px solid var(--info);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Active Requests</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--info);">
            {{ $stats['active_requests'] }}
        </div>
        <a href="{{ route('user.requests') }}" style="font-size: 12px; font-weight: 600;">Track requests &rarr;</a>
    </div>

    <div class="card" style="border-left: 4px solid var(--warning);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Pending Claims</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--warning);">
            {{ $stats['pending_claims'] }}
        </div>
        <span style="font-size: 12px; color: var(--text-muted);">Ready for pickup</span>
    </div>

    <div class="card" style="border-left: 4px solid var(--success);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Completed Requests</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--success);">
            {{ $stats['completed_requests'] }}
        </div>
        <span style="font-size: 12px; color: var(--text-muted);">Delivered impact</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 24px;">
    <!-- Left Column: Recent Items -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Recent Donations -->
        <div class="card">
            <div class="card-header">
                <h3>My Recent Donations</h3>
                <a href="{{ route('user.donations') }}" class="btn btn-outline btn-sm">All Donations</a>
            </div>

            @if($recentDonations->isEmpty())
                <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                    You haven't posted any physical donations yet.
                </p>
                <div style="text-align: center;">
                    <a href="{{ route('donations.create') }}" class="btn btn-accent btn-sm">
                        <x-icon name="gift" size="14" /> Post First Donation
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Title</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentDonations as $donation)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">{{ Str::limit($donation->title, 35) }}</div>
                                        <div style="font-size: 12px; color: var(--text-muted);">{{ $donation->category->name }}</div>
                                    </td>
                                    <td>{{ $donation->quantity_available }} / {{ $donation->quantity_total }} {{ $donation->unit }}</td>
                                    <td>
                                        <span class="status-pill status-{{ $donation->status }}">
                                            {{ str_replace('_', ' ', $donation->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('donations.preview', $donation->id) }}" class="btn btn-outline btn-sm">Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Recent Requests -->
        <div class="card">
            <div class="card-header">
                <h3>My Resource Requests</h3>
                <a href="{{ route('user.requests') }}" class="btn btn-outline btn-sm">All Requests</a>
            </div>

            @if($recentRequests->isEmpty())
                <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                    You haven't submitted any resource requests.
                </p>
            @else
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Requested</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequests as $req)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">{{ Str::limit($req->item_name, 35) }}</div>
                                        <div style="font-size: 12px; color: var(--text-muted);">Urgency: {{ ucfirst($req->urgency_level) }}</div>
                                    </td>
                                    <td>{{ $req->quantity_requested }}</td>
                                    <td>
                                        <span class="status-pill status-{{ $req->status }}">
                                            {{ str_replace('_', ' ', $req->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('requests.show', $req->id) }}" class="btn btn-outline btn-sm">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Transactions & Notifications -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Active Claims / Transactions -->
        <div class="card">
            <div class="card-header">
                <h3>Active Claims &amp; Pickups</h3>
            </div>

            @if($recentTransactions->isEmpty())
                <p style="color: var(--text-muted); text-align: center; padding: 16px 0; font-size: 13px;">
                    No pending claims or transactions right now.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($recentTransactions as $tx)
                        <div style="background: var(--surface-soft); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <strong style="font-size: 13px; color: var(--brand-primary);">{{ $tx->claim_code }}</strong>
                                <span class="status-pill status-{{ $tx->status }}">{{ str_replace('_', ' ', $tx->status) }}</span>
                            </div>
                            <div style="font-size: 13px; font-weight: 600; margin-bottom: 4px;">{{ $tx->donation->title ?? 'Donation Item' }}</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
                                Quantity: {{ $tx->quantity }} &middot; Pickup: {{ $tx->pickup_date ? $tx->pickup_date->format('M d, Y') : 'Scheduled' }}
                            </div>
                            <a href="{{ route('claims.show', $tx->id) }}" class="btn btn-primary btn-sm" style="width: 100%;">
                                View Claim Slip &rarr;
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Notifications -->
        <div class="card">
            <div class="card-header">
                <h3>Notifications</h3>
                <a href="{{ route('notifications.index') }}" style="font-size: 12px;">View All</a>
            </div>

            @if($notifications->isEmpty())
                <p style="color: var(--text-muted); text-align: center; padding: 14px 0; font-size: 13px;">No new alerts.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($notifications as $notif)
                        <div style="padding: 10px; border-radius: var(--radius-sm); background: {{ $notif->is_read ? 'transparent' : 'var(--surface-soft)' }}; border-bottom: 1px solid var(--border);">
                            <div style="font-weight: 600; font-size: 13px; color: var(--text);">{{ $notif->title }}</div>
                            <div style="font-size: 12px; color: var(--text-soft); margin-top: 2px;">{{ Str::limit($notif->message, 80) }}</div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">{{ $notif->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
