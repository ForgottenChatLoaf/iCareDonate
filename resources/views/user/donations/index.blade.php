@extends('layouts.user')

@section('title', 'My Posted Donations')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-approved" style="margin-bottom: 6px;">
            <x-icon name="package" size="14" /> Inventory &amp; Records
        </span>
        <h1>My Donations History</h1>
        <p>Monitor your active physical donation offers, remaining available counts, and verification statuses.</p>
    </div>
    <a href="{{ route('donations.create') }}" class="btn btn-accent">
        <x-icon name="gift" size="16" /> + Post New Donation
    </a>
</div>

<div class="card">
    @if($donations->isEmpty())
        <div style="text-align: center; padding: 48px 20px;">
            <div style="color: var(--text-muted); margin-bottom: 12px;">
                <x-icon name="gift" size="48" />
            </div>
            <h3>No Donations Posted Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 16px;">
                You haven't listed any physical resources on iCareDonate. Share what you can today!
            </p>
            <a href="{{ route('donations.create') }}" class="btn btn-accent btn-sm">
                <x-icon name="gift" size="14" /> Post a Donation
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>Reserved</th>
                        <th>Claimed</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($donations as $donation)
                        <tr>
                            <td>#{{ $donation->id }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text);">{{ $donation->title }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Submitted {{ $donation->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td>{{ $donation->category->name }}</td>
                            <td><strong>{{ $donation->quantity_total }}</strong> {{ $donation->unit }}</td>
                            <td><strong style="color: var(--success);">{{ $donation->quantity_available }}</strong></td>
                            <td>{{ $donation->quantity_reserved }}</td>
                            <td><strong style="color: var(--brand-primary);">{{ $donation->quantity_claimed }}</strong></td>
                            <td>
                                <span class="status-pill status-{{ $donation->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $donation->status)) }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="{{ route('donations.preview', $donation->id) }}" class="btn btn-outline btn-sm" title="Preview Details">
                                        View
                                    </a>
                                    @if(in_array($donation->status, ['draft', 'pending_verification', 'needs_information']))
                                        <a href="{{ route('donations.edit', $donation->id) }}" class="btn btn-outline btn-sm" title="Edit">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $donations->links() }}
        </div>
    @endif
</div>
@endsection
