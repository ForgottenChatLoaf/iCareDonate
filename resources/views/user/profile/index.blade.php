@extends('layouts.user')

@section('title', 'Profile & Account Settings')

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span class="status-pill status-approved" style="margin-bottom: 6px;">Settings &amp; Security</span>
        <h1>Profile &amp; Account Settings</h1>
        <p>Manage your personal contact details, location settings, and account password.</p>
    </div>

    <!-- Personal Profile Form -->
    <div class="card" style="padding: 32px; margin-bottom: 24px;">
        <h3 style="color: var(--brand-primary); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            1. Personal &amp; Logistics Information
        </h3>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Email Address (Read-only)</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled style="background: var(--surface-soft); cursor: not-allowed;">
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Email cannot be changed directly for verification security.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone / Mobile Number <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="address">Street Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $user->address) }}" placeholder="e.g. 12 Acacia St.">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay', $user->barangay) }}" placeholder="e.g. Bel-Air">
                </div>

                <div class="form-group">
                    <label for="city">City / Municipality</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $user->city) }}" placeholder="e.g. Makati">
                </div>

                <div class="form-group">
                    <label for="province">Province / Region</label>
                    <input type="text" id="province" name="province" value="{{ old('province', $user->province) }}" placeholder="e.g. Metro Manila">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <x-icon name="check" size="16" /> Save Profile Details
            </button>
        </form>
    </div>

    <!-- Password Change Form -->
    <div class="card" style="padding: 32px; margin-bottom: 24px;">
        <h3 style="color: var(--brand-primary); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            2. Change Password
        </h3>

        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password">Current Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="current_password" name="current_password" required placeholder="Enter existing password">
                @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">New Password <span style="color:var(--danger)">*</span> (Min 8 chars)</label>
                    <input type="password" id="password" name="password" required placeholder="New password">
                    @error('password')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password <span style="color:var(--danger)">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter new password">
                </div>
            </div>

            <button type="submit" class="btn btn-outline" style="margin-top: 10px;">
                Update Password
            </button>
        </form>
    </div>

    <!-- Account Details & Linked Services -->
    <div class="card" style="padding: 32px;">
        <h3 style="color: var(--brand-primary); margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            3. Account Status &amp; Connected Services
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted); display: block;">Account Role:</span>
                <strong>{{ ucfirst($user->role) }}</strong>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Account Verification Status:</span>
                <span class="status-pill status-{{ $user->status }}">{{ ucfirst(str_replace('_', ' ', $user->status)) }}</span>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Registered Since:</span>
                <strong>{{ $user->created_at->format('M d, Y') }}</strong>
            </div>

            <div>
                <span style="color: var(--text-muted); display: block;">Active Browser Session:</span>
                <span class="status-pill status-approved"><x-icon name="check" size="12" /> Active (Current)</span>
            </div>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
            <div style="font-size: 13px; font-weight: 700; margin-bottom: 10px;">External Account Integrations:</div>
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div style="padding: 10px 16px; background: var(--surface-soft); border-radius: var(--radius-md); border: 1px solid var(--border); font-size: 13px;">
                    Google Account: <span style="color: var(--text-muted); font-weight: 600;">Not Connected</span>
                </div>
                <div style="padding: 10px 16px; background: var(--surface-soft); border-radius: var(--radius-md); border: 1px solid var(--border); font-size: 13px;">
                    Facebook Account: <span style="color: var(--text-muted); font-weight: 600;">Not Connected</span>
                </div>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 6px;">External OAuth providers are disabled for secure direct local account authentication.</div>
        </div>
    </div>
</div>
@endsection
