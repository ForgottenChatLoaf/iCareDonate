@extends('layouts.admin')

@section('title', 'Admin — Community Support Desk')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-info" style="margin-bottom: 6px;">
        <x-icon name="message" size="14" /> Help Center
    </span>
    <h1>Community Support &amp; Inquiries</h1>
    <p>Review submitted user concerns, provide official administrative replies, and resolve issues.</p>
</div>

<div class="card">
    @if($tickets->isEmpty())
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <x-icon name="message" size="36" style="margin-bottom: 8px;" />
            <p>No support tickets or inquiries logged.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>User / Contact</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $t)
                        <tr>
                            <td>#{{ $t->id }}</td>
                            <td>
                                <div>{{ $t->name ?? ($t->user->name ?? 'Guest') }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $t->email ?? ($t->user->email ?? '') }}</div>
                            </td>
                            <td>{{ ucfirst($t->category) }}</td>
                            <td><strong>{{ Str::limit($t->subject, 32) }}</strong></td>
                            <td>
                                <span class="status-pill status-{{ $t->status === 'resolved' ? 'approved' : 'pending' }}">
                                    {{ ucfirst(str_replace('_', ' ', $t->status)) }}
                                </span>
                            </td>
                            <td>{{ $t->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.support.show', $t->id) }}" class="btn btn-outline btn-sm">
                                    Reply &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
