@extends('layouts.admin')

@section('title', 'Admin — User Directory & Moderation')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <span class="status-pill status-approved" style="margin-bottom: 6px;">
            <x-icon name="users" size="14" /> Community Governance
        </span>
        <h1>User Moderation &amp; Accounts</h1>
        <p>Oversee registered donors, requesters, and administrator credentials with transparent accountability records.</p>
    </div>
</div>

<!-- Search & Role/Status Filters -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
        <div style="flex: 2; min-width: 220px;">
            <label for="search">Search Users</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email address, city...">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User (Donor/Requester)</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <label for="status">Account Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="banned" {{ request('status') == 'banned' ? 'selected' : '' }}>Banned</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Donations</th>
                    <th>Requests</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td>#{{ $u->id }}</td>
                        <td>
                            <div style="font-weight: 700;">{{ $u->name }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $u->city ?? 'Metro Manila' }}</div>
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>
                            <span class="status-pill status-{{ $u->role === 'admin' ? 'approved' : 'info' }}">
                                {{ ucfirst($u->role) }}
                            </span>
                        </td>
                        <td><strong>{{ $u->donations_count }}</strong></td>
                        <td><strong>{{ $u->requests_count }}</strong></td>
                        <td>
                            <span class="status-pill status-{{ $u->status === 'active' ? 'approved' : 'rejected' }}">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-outline btn-sm">
                                Moderate &rarr;
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 18px;">
        {{ $users->links() }}
    </div>
</div>
@endsection
