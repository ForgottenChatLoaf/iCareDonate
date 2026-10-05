@extends('layouts.admin')

@section('title', 'Admin — Operational Reports & Platform Analytics')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-approved" style="margin-bottom: 6px;">
            <x-icon name="bar-chart" size="14" /> Analytics &amp; Accountability
        </span>
        <h1>Platform Reports &amp; Statistics</h1>
        <p>Comprehensive system summaries, non-monetary resource distribution metrics, and browser print reports.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('admin.reports.print') }}" target="_blank" class="btn btn-outline">
            <x-icon name="printer" size="16" /> Printable Report (PDF)
        </a>
    </div>
</div>

<!-- Aggregated Metrics -->
<div class="grid-4" style="margin-bottom: 32px;">
    <div class="card">
        <div style="font-size: 13px; color: var(--text-muted);">Total Physical Items Offered</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--brand-primary); margin: 4px 0;">
            {{ number_format($stats['total_items_offered']) }}
        </div>
        <div style="font-size: 12px; color: var(--text-soft);">Across {{ $stats['total_donations'] }} donation batches</div>
    </div>

    <div class="card">
        <div style="font-size: 13px; color: var(--text-muted);">Total Items Successfully Claimed</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--success); margin: 4px 0;">
            {{ number_format($stats['total_items_claimed']) }}
        </div>
        <div style="font-size: 12px; color: var(--text-soft);">Directly delivered to beneficiaries</div>
    </div>

    <div class="card">
        <div style="font-size: 13px; color: var(--text-muted);">Community Assistance Requests</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--info); margin: 4px 0;">
            {{ $stats['total_requests'] }}
        </div>
        <div style="font-size: 12px; color: var(--text-soft);">{{ $stats['approved_requests'] }} approved in matching pool</div>
    </div>

    <div class="card">
        <div style="font-size: 13px; color: var(--text-muted);">Completed Handoff Transfers</div>
        <div style="font-size: 2rem; font-family: var(--font-heading); color: var(--brand-primary); margin: 4px 0;">
            {{ $stats['completed_transactions'] }}
        </div>
        <div style="font-size: 12px; color: var(--text-soft);">100% verified claim receipts</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    <!-- Category Distribution -->
    <div class="card">
        <div class="card-header">
            <h3>Inventory by Category</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Donations</th>
                        <th>Requests</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong></td>
                            <td>{{ $c->donations_count }} listings</td>
                            <td>{{ $c->requests_count }} requests</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- User & Operational Overview -->
    <div class="card">
        <div class="card-header">
            <h3>Platform Operations Overview</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                <span style="color: var(--text-muted);">Total Registered Users:</span>
                <strong>{{ $stats['total_users'] }} ({{ $stats['active_users'] }} active)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                <span style="color: var(--text-muted);">Total Match Allocations Created:</span>
                <strong>{{ $stats['total_transactions'] }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                <span style="color: var(--text-muted);">Open Support Inquiries:</span>
                <strong style="color: var(--warning);">{{ $stats['open_tickets'] }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 4px;">
                <span style="color: var(--text-muted);">Platform Integrity:</span>
                <strong style="color: var(--success);">Physical Non-Cash Compliant</strong>
            </div>
        </div>
    </div>
</div>
@endsection
