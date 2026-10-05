@extends('layouts.admin')

@section('title', 'Administrator Control Center')

@section('content')
<div style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="status-pill status-verified" style="margin-bottom: 6px;">
                <x-icon name="shield" size="14" /> Central Platform Operations
            </span>
            <h1>Operations Dashboard</h1>
            <p>Live inventory, verification queue, resource matching, and security oversight.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.matches.index') }}" class="btn btn-accent">
                <x-icon name="repeat" size="16" /> + Match Resources
            </a>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline">
                <x-icon name="bar-chart" size="16" /> View Reports
            </a>
        </div>
    </div>
</div>

<!-- KPI Metrics Grid -->
<div class="grid-4" style="margin-bottom: 32px;">
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Pending Donation Verifications</div>
        <div style="font-size: 2.2rem; font-family: var(--font-heading); color: var(--warning);">
            {{ $kpi['pending_donations'] }}
        </div>
        <a href="{{ route('admin.donations.index', ['status' => 'pending_verification']) }}" style="font-size: 12px; font-weight: 600;">
            Review pending donations &rarr;
        </a>
    </div>

    <div class="card" style="border-left: 4px solid var(--info);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Pending Requests to Assess</div>
        <div style="font-size: 2.2rem; font-family: var(--font-heading); color: var(--info);">
            {{ $kpi['pending_requests'] }}
        </div>
        <a href="{{ route('admin.requests.index', ['status' => 'submitted']) }}" style="font-size: 12px; font-weight: 600;">
            Review applications &rarr;
        </a>
    </div>

    <div class="card" style="border-left: 4px solid var(--brand-primary);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Active Pickup Claims</div>
        <div style="font-size: 2.2rem; font-family: var(--font-heading); color: var(--brand-primary);">
            {{ $kpi['active_claims'] }}
        </div>
        <a href="{{ route('admin.transactions.index', ['status' => 'pending_claim']) }}" style="font-size: 12px; font-weight: 600;">
            Track active claims &rarr;
        </a>
    </div>

    <div class="card" style="border-left: 4px solid var(--success);">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Completed Handoffs</div>
        <div style="font-size: 2.2rem; font-family: var(--font-heading); color: var(--success);">
            {{ $kpi['completed_transactions'] }}
        </div>
        <span style="font-size: 12px; color: var(--text-muted);">Verified transfers</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 32px;">
    <!-- Pending Donations Queue -->
    <div class="card">
        <div class="card-header">
            <h3>Pending Donation Verification Queue</h3>
            <a href="{{ route('admin.donations.index') }}" class="btn btn-outline btn-sm">All Donations</a>
        </div>

        @if($pendingDonations->isEmpty())
            <div style="text-align: center; padding: 32px 0; color: var(--text-muted);">
                <x-icon name="check-circle" size="32" style="color: var(--success); margin-bottom: 8px;" />
                <p>No donations awaiting verification. All caught up!</p>
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Donation</th>
                            <th>Quantity</th>
                            <th>Donor</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingDonations as $d)
                            <tr>
                                <td>
                                    <div style="font-weight: 700;">{{ Str::limit($d->title, 32) }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $d->category->name }}</div>
                                </td>
                                <td>{{ $d->quantity_total }} {{ $d->unit }}</td>
                                <td>{{ $d->donor_name }}</td>
                                <td>
                                    <a href="{{ route('admin.donations.show', $d->id) }}" class="btn btn-primary btn-sm">
                                        Inspect &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Pending Requests Queue -->
    <div class="card">
        <div class="card-header">
            <h3>Pending Request Applications</h3>
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline btn-sm">All Requests</a>
        </div>

        @if($pendingRequests->isEmpty())
            <div style="text-align: center; padding: 32px 0; color: var(--text-muted);">
                <x-icon name="check-circle" size="32" style="color: var(--success); margin-bottom: 8px;" />
                <p>No pending resource requests awaiting proof review.</p>
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item Needed</th>
                            <th>Urgency</th>
                            <th>Requester</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingRequests as $r)
                            <tr>
                                <td>
                                    <div style="font-weight: 700;">{{ Str::limit($r->item_name, 28) }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Qty: {{ $r->quantity_requested }}</div>
                                </td>
                                <td>
                                    <span class="status-pill status-{{ $r->urgency_level === 'emergency' ? 'rejected' : 'info' }}">
                                        {{ ucfirst($r->urgency_level) }}
                                    </span>
                                </td>
                                <td>{{ $r->requester->name }}</td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $r->id) }}" class="btn btn-primary btn-sm">
                                        Assess &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px;">
    <!-- Recent Transactions & Claims -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Transactions &amp; Claims</h3>
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline btn-sm">All Transactions</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $tx)
                        <tr>
                            <td><strong style="color: var(--brand-primary); font-family: monospace;">{{ $tx->claim_code }}</strong></td>
                            <td>{{ Str::limit($tx->donation->title, 25) }}</td>
                            <td>{{ $tx->quantity }}</td>
                            <td>{{ $tx->requester->name }}</td>
                            <td>
                                <span class="status-pill status-{{ $tx->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $tx->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.transactions.show', $tx->id) }}" class="btn btn-outline btn-sm">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live System Audit Trail -->
    <div class="card">
        <div class="card-header">
            <h3>Accountability Audit Log</h3>
            <a href="{{ route('admin.audit-logs.index') }}" style="font-size: 12px;">Full Log &rarr;</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($recentAuditLogs as $log)
                <div style="padding: 10px; background: var(--surface-soft); border-radius: var(--radius-sm); border: 1px solid var(--border); font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                        <strong style="color: var(--brand-primary);">{{ $log->action }}</strong>
                        <span style="color: var(--text-muted);">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="color: var(--text-soft);">{{ Str::limit($log->details, 70) }}</div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                        Actor: {{ $log->actor_name ?? 'System' }} ({{ $log->actor_role ?? 'sys' }})
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
