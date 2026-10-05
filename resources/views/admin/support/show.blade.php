@extends('layouts.admin')

@section('title', 'Admin — Reply to Support Ticket #' . $ticket->id)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $ticket->status === 'resolved' ? 'approved' : 'pending' }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </span>
            <h1>Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h1>
            <p>From: <strong>{{ $ticket->name ?? ($ticket->user->name ?? 'Guest User') }}</strong> ({{ $ticket->email ?? ($ticket->user->email ?? '') }})</p>
        </div>
        <a href="{{ route('admin.support.index') }}" class="btn btn-outline">&larr; Back to Tickets</a>
    </div>

    <!-- User Inquiry Message -->
    <div class="card" style="padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--brand-primary); margin-bottom: 12px;">User Inquiry / Concern</h3>
        <p style="white-space: pre-line; line-height: 1.7; font-size: 14px; margin: 0;">{{ $ticket->message }}</p>
    </div>

    <!-- Administrative Response Box -->
    <div class="card" style="padding: 28px; border: 2px solid var(--border-strong);">
        <h3 style="margin-bottom: 16px;">Administrative Reply &amp; Resolution</h3>

        <form action="{{ route('admin.support.reply', $ticket->id) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="admin_reply">Official Response Message <span style="color:var(--danger)">*</span></label>
                <textarea id="admin_reply" name="admin_reply" rows="5" required placeholder="Type official response to user. This will notify the user in their notifications feed.">{{ old('admin_reply', $ticket->admin_reply) }}</textarea>
                @error('admin_reply')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row" style="margin-bottom: 20px;">
                <div class="form-group" style="max-width: 240px;">
                    <label for="status">Ticket Status <span style="color:var(--danger)">*</span></label>
                    <select id="status" name="status" required>
                        <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <x-icon name="check-circle" size="16" /> Send Reply &amp; Update Status
            </button>
        </form>
    </div>
</div>
@endsection
