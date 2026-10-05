@extends('layouts.admin')

@section('title', 'Admin — Accountability & System Audit Trail')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-approved" style="margin-bottom: 6px;">
        <x-icon name="shield" size="14" /> Accountability
    </span>
    <h1>System Audit Logs &amp; Audit Trail</h1>
    <p>Immutable record of all administrative verifications, user moderation actions, status changes, and allocations.</p>
</div>

<!-- Filter Box -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <form action="{{ route('admin.audit-logs.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
        <div style="flex: 2; min-width: 220px;">
            <label for="action">Action Filter</label>
            <input type="text" id="action" name="action" value="{{ request('action') }}" placeholder="e.g. APPROVED, REJECTED, LOGIN, MODERATION">
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label for="target_type">Target Entity</label>
            <select id="target_type" name="target_type">
                <option value="">All Entities</option>
                <option value="donation" {{ request('target_type') == 'donation' ? 'selected' : '' }}>Donations</option>
                <option value="request" {{ request('target_type') == 'request' ? 'selected' : '' }}>Requests</option>
                <option value="match" {{ request('target_type') == 'match' ? 'selected' : '' }}>Matches</option>
                <option value="transaction" {{ request('target_type') == 'transaction' ? 'selected' : '' }}>Transactions</option>
                <option value="user" {{ request('target_type') == 'user' ? 'selected' : '' }}>Users</option>
                <option value="interview" {{ request('target_type') == 'interview' ? 'selected' : '' }}>Interviews</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter Logs</button>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Actor</th>
                    <th>Target</th>
                    <th>Audit Details &amp; Reason</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td style="white-space: nowrap; font-size: 12px; color: var(--text-muted);">
                            {{ $log->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td>
                            <strong style="color: var(--brand-primary); font-size: 13px;">{{ $log->action }}</strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $log->actor_name ?? 'System' }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ ucfirst($log->actor_role ?? 'sys') }}</div>
                        </td>
                        <td>
                            <span class="status-pill status-info" style="font-size: 11px;">
                                {{ ucfirst($log->target_type ?? 'platform') }} #{{ $log->target_id ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 13px; color: var(--text);">{{ $log->details }}</div>
                            @if($log->reason)
                                <div style="font-size: 11px; color: var(--danger); margin-top: 3px;">
                                    <strong>Reason:</strong> {{ $log->reason }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size: 12px; color: var(--text-muted); font-family: monospace;">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 18px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
