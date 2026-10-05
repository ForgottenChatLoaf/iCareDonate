@extends('layouts.admin')

@section('title', 'Admin — Donation Review & Verification')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-approved" style="margin-bottom: 6px;">Safety &amp; Inventory</span>
        <h1>Donations Verification Management</h1>
        <p>Inspect incoming donation offers, verify physical conditions, and approve resources for public claiming.</p>
    </div>
</div>

<!-- Search & Status Filter -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <form action="{{ route('admin.donations.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
        <div style="flex: 2; min-width: 220px;">
            <label for="search">Search Donations</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Title, donor name, city...">
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label for="status">Verification Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                <option value="needs_information" {{ request('status') == 'needs_information' ? 'selected' : '' }}>Needs More Information</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved &amp; Available</option>
                <option value="fully_allocated" {{ request('status') == 'fully_allocated' ? 'selected' : '' }}>Fully Allocated</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.donations.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    @if($donations->isEmpty())
        <div style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
            <x-icon name="package" size="40" style="margin-bottom: 8px;" />
            <p>No donation records match the selected filters.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>Stock (Avail / Tot)</th>
                        <th>Donor</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($donations as $donation)
                        <tr>
                            <td>#{{ $donation->id }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text);">{{ Str::limit($donation->title, 36) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    Submitted {{ $donation->submitted_at ? $donation->submitted_at->format('M d, Y') : $donation->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td>{{ $donation->category->name }}</td>
                            <td>
                                <strong>{{ $donation->quantity_available }}</strong> / {{ $donation->quantity_total }} {{ $donation->unit }}
                            </td>
                            <td>{{ $donation->donor_name ?? $donation->donor->name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $donation->condition_type)) }}</td>
                            <td>
                                <span class="status-pill status-{{ $donation->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $donation->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.donations.show', $donation->id) }}" class="btn btn-primary btn-sm">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $donations->links() }}
        </div>
    @endif
</div>
@endsection
