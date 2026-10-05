@extends('layouts.user')

@section('title', 'My Support Inquiries & Concerns')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-info" style="margin-bottom: 6px;">
            <x-icon name="message" size="14" /> Support Center
        </span>
        <h1>My Concerns &amp; Inquiries</h1>
        <p>View submitted questions, platform concerns, and replies from our administrative staff.</p>
    </div>
    <a href="{{ route('support.create') }}" class="btn btn-primary">
        <x-icon name="mail" size="16" /> + Submit New Concern
    </a>
</div>

<div class="card">
    @if($tickets->isEmpty())
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <x-icon name="message" size="36" style="margin-bottom: 8px;" />
            <p>You haven't submitted any concerns or support tickets.</p>
            <a href="{{ route('support.create') }}" class="btn btn-outline btn-sm" style="margin-top: 10px;">Contact Support</a>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $t)
                        <tr>
                            <td>#{{ $t->id }}</td>
                            <td><strong>{{ $t->subject }}</strong></td>
                            <td>{{ ucfirst($t->category) }}</td>
                            <td>
                                <span class="status-pill status-{{ $t->status === 'resolved' ? 'approved' : ($t->status === 'in_progress' ? 'info' : 'pending') }}">
                                    {{ ucfirst(str_replace('_', ' ', $t->status)) }}
                                </span>
                            </td>
                            <td>{{ $t->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('support.show', $t->id) }}" class="btn btn-outline btn-sm">View Thread</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
