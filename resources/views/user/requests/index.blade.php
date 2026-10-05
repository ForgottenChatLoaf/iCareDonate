@extends('layouts.user')

@section('title', 'My Resource Requests')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-info" style="margin-bottom: 6px;">Assistance Applications</span>
        <h1>My Resource Requests</h1>
        <p>Track your submitted requests, proof review progress, and active claim slips.</p>
    </div>
    <a href="{{ route('donations.index') }}" class="btn btn-primary">
        🔍 Browse Available Resources
    </a>
</div>

<div class="card">
    @if($requests->isEmpty())
        <div style="text-align: center; padding: 48px 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">📋</div>
            <h3>No Requests Submitted</h3>
            <p style="color: var(--text-muted); margin-bottom: 16px;">
                You haven't applied for any resources yet. Browse available donations to request items.
            </p>
            <a href="{{ route('donations.index') }}" class="btn btn-primary btn-sm">Browse Donations</a>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Item Requested</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Matched</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        <tr>
                            <td>#{{ $req->id }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text);">{{ $req->item_name }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Submitted {{ $req->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td><strong>{{ $req->quantity_requested }}</strong></td>
                            <td>
                                <span class="status-pill status-{{ $req->urgency_level === 'emergency' || $req->urgency_level === 'high' ? 'rejected' : 'info' }}">
                                    {{ ucfirst($req->urgency_level) }}
                                </span>
                            </td>
                            <td>{{ $req->quantity_matched }} / {{ $req->quantity_requested }}</td>
                            <td>
                                <span class="status-pill status-{{ $req->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('requests.show', $req->id) }}" class="btn btn-outline btn-sm">
                                    Track Status &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
