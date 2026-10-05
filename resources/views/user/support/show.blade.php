@extends('layouts.user')

@section('title', 'Support Ticket #' . $ticket->id)

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <span class="status-pill status-{{ $ticket->status === 'resolved' ? 'approved' : 'pending' }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </span>
            <h1>Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h1>
            <p>Category: <strong>{{ ucfirst($ticket->category) }}</strong> &middot; Opened {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <a href="{{ route('support.index') }}" class="btn btn-outline">&larr; Back to Concerns</a>
    </div>

    <!-- User Original Concern -->
    <div class="card" style="padding: 24px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            <x-icon name="user" size="18" style="color: var(--brand-primary);" />
            <strong>{{ $ticket->name ?? auth()->user()->name }}</strong>
            <span style="font-size: 12px; color: var(--text-muted);">&middot; {{ $ticket->created_at->diffForHumans() }}</span>
        </div>
        <p style="white-space: pre-line; margin: 0; line-height: 1.6;">{{ $ticket->message }}</p>
    </div>

    <!-- Official Staff Response -->
    <div class="card" style="padding: 24px; border: 2px solid {{ $ticket->admin_reply ? 'var(--brand-primary)' : 'var(--border)' }}; background: var(--surface-soft);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            <x-icon name="shield" size="18" style="color: var(--brand-primary);" />
            <strong style="color: var(--brand-primary);">Support Administration Team</strong>
            @if($ticket->replied_at)
                <span style="font-size: 12px; color: var(--text-muted);">&middot; {{ $ticket->replied_at->diffForHumans() }}</span>
            @endif
        </div>

        @if($ticket->admin_reply)
            <p style="white-space: pre-line; margin: 0; line-height: 1.6; font-size: 14px;">{{ $ticket->admin_reply }}</p>
            @if($ticket->status === 'resolved')
                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border); font-size: 12px; color: var(--success); font-weight: 600;">
                    <x-icon name="check-circle" size="14" /> Marked as resolved by admin.
                </div>
            @endif
        @else
            <div style="padding: 16px 0; color: var(--text-muted); font-size: 13px;">
                <p style="margin: 0;">Our community support team is reviewing your inquiry. Responses typically arrive within 24 hours.</p>
            </div>
        @endif
    </div>
</div>
@endsection
