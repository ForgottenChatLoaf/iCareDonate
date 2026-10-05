@extends('layouts.user')

@section('title', 'Notifications')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <span class="status-pill status-info" style="margin-bottom: 6px;">
                <x-icon name="bell" size="14" /> Activity Alerts
            </span>
            <h1>Notifications</h1>
            <p>System updates regarding your donations, requests, and scheduled claims.</p>
        </div>
        <div>
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Mark All as Read</button>
            </form>
        </div>
    </div>

    <div class="card" style="padding: 16px;">
        @if($notifications->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <x-icon name="bell" size="36" style="margin-bottom: 8px;" />
                <p>You have no notifications right now.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column;">
                @foreach($notifications as $notif)
                    <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; background: {{ $notif->is_read ? 'transparent' : 'var(--surface-soft)' }}; border-radius: var(--radius-sm); margin-bottom: 6px;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                @if(!$notif->is_read)
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--brand-accent); display: inline-block;"></span>
                                @endif
                                <strong style="font-size: 14px; color: var(--text);">{{ $notif->title }}</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">&middot; {{ $notif->created_at->diffForHumans() }}</span>
                            </div>
                            <p style="font-size: 13px; color: var(--text-soft); margin: 0;">{{ $notif->message }}</p>
                        </div>
                        @if(!$notif->is_read)
                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" style="font-size: 11px; padding: 4px 8px;">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 16px;">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
