@extends('layouts.admin')

@section('title', 'Admin — Review Resource Requests')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-info" style="margin-bottom: 6px;">Applications Review</span>
        <h1>Beneficiary Resource Requests</h1>
        <p>Assess assistance applications, review supporting certificates/proofs, and approve items for resource matching.</p>
    </div>
</div>

<!-- Search & Status Filter -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <form action="{{ route('admin.requests.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
        <div style="flex: 1; min-width: 180px;">
            <label for="status">Application Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted (New)</option>
                <option value="proof_review" {{ request('status') == 'proof_review' ? 'selected' : '' }}>Proof Review</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved (In Matching Pool)</option>
                <option value="matched" {{ request('status') == 'matched' ? 'selected' : '' }}>Matched</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label for="urgency">Urgency Level</label>
            <select id="urgency" name="urgency">
                <option value="">All Urgencies</option>
                <option value="emergency" {{ request('urgency') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                <option value="high" {{ request('urgency') == 'high' ? 'selected' : '' }}>High Priority</option>
                <option value="medium" {{ request('urgency') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Low</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    @if($requests->isEmpty())
        <div style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
            <x-icon name="file-text" size="40" style="margin-bottom: 8px;" />
            <p>No requests found matching the current criteria.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Item Requested</th>
                        <th>Qty Needed</th>
                        <th>Urgency</th>
                        <th>Requester</th>
                        <th>City / Address</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        <tr>
                            <td>#{{ $req->id }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text);">{{ Str::limit($req->item_name, 34) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    Submitted {{ $req->submitted_at ? $req->submitted_at->format('M d, Y') : $req->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td><strong>{{ $req->quantity_requested }}</strong></td>
                            <td>
                                <span class="status-pill status-{{ $req->urgency_level === 'emergency' ? 'rejected' : ($req->urgency_level === 'high' ? 'pending' : 'info') }}">
                                    {{ ucfirst($req->urgency_level) }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $req->requester->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $req->contact_number }}</div>
                            </td>
                            <td>{{ Str::limit($req->delivery_address, 25) }}</td>
                            <td>
                                <span class="status-pill status-{{ $req->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.requests.show', $req->id) }}" class="btn btn-primary btn-sm">
                                    Assess &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
