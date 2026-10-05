@extends('layouts.admin')

@section('title', 'Admin — Moderate User #' . $user->id)

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $user->status === 'active' ? 'approved' : 'rejected' }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst($user->status) }}
            </span>
            <h1>User #{{ $user->id }}: {{ $user->name }}</h1>
            <p>{{ $user->email }} &middot; Registered {{ $user->created_at->format('M d, Y') }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">&larr; Back to Directory</a>
    </div>

    @if($user->suspension_reason)
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <span><x-icon name="alert" size="16" /> Account Restricted. Recorded Moderation Reason: <em>{{ $user->suspension_reason }}</em></span>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 24px;">
        <!-- Left: User Profile & History -->
        <div>
            <div class="card" style="margin-bottom: 24px;">
                <h3 style="color: var(--brand-primary); margin-bottom: 16px;">User Profile &amp; Contact</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 13px;">
                    <div>
                        <span style="color: var(--text-muted); display: block;">Full Name:</span>
                        <strong>{{ $user->name }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Username:</span>
                        <strong>{{ $user->username ?? 'None' }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Phone Number:</span>
                        <strong>{{ $user->phone ?? 'None' }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Role / Permission:</span>
                        <strong>{{ ucfirst($user->role) }}</strong>
                    </div>
                    <div style="grid-column: span 2;">
                        <span style="color: var(--text-muted); display: block;">Address &amp; Location:</span>
                        <strong>{{ $user->address ?? '' }} {{ $user->barangay ? 'Brgy. ' . $user->barangay : '' }} {{ $user->city ?? '' }} {{ $user->province ?? '' }}</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border); font-size: 13px;">
                    <div>Total Donations Posted: <strong>{{ $user->donations_count }}</strong></div>
                    <div>Total Requests Filed: <strong>{{ $user->requests_count }}</strong></div>
                </div>
            </div>

            <!-- Activity Audit Logs -->
            <div class="card">
                <h3 style="margin-bottom: 14px;">Recent Account Audit Logs</h3>
                @if($auditLogs->isEmpty())
                    <p style="color: var(--text-muted); font-size: 13px;">No direct moderation audit logs recorded for this user.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach($auditLogs as $log)
                            <div style="padding: 10px; background: var(--surface-soft); border-radius: var(--radius-sm); font-size: 12px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <strong style="color: var(--brand-primary);">{{ $log->action }}</strong>
                                    <span style="color: var(--text-muted);">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                                </div>
                                <div style="color: var(--text-soft); margin-top: 2px;">{{ $log->details }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Moderation Actions -->
        <div>
            <div class="card" style="padding: 24px; border: 2px solid var(--border-strong);">
                <h3 style="margin-bottom: 16px;">Moderation Actions</h3>

                <form action="{{ route('admin.users.moderate', $user->id) }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="action">Select Enforcement Action <span style="color:var(--danger)">*</span></label>
                        <select id="action" name="action" required>
                            <option value="warn">Issue Official Warning (Notification)</option>
                            <option value="suspend" {{ $user->status === 'suspended' ? 'disabled' : '' }}>Temporary Suspension</option>
                            <option value="ban" {{ $user->status === 'banned' ? 'disabled' : '' }}>Permanent Ban</option>
                            @if(in_array($user->status, ['suspended', 'banned']))
                                <option value="restore" selected>Restore Account to Active</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reason">Accountability Reason &amp; Explanation <span style="color:var(--danger)">*</span></label>
                        <textarea id="reason" name="reason" rows="3" required placeholder="Specify reason for moderation action (recorded in audit trail)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger" style="width: 100%;" onclick="return confirm('Confirm moderation action on this account?');">
                        Execute Moderation Action
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
