@extends('layouts.public')

@section('title', 'Join iCareDonate — Member Registration')

@section('content')
<div class="container container-narrow" style="margin-top: 36px; margin-bottom: 48px;">
    <div class="card" style="padding: 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-family: var(--font-heading); font-size: 28px; color: var(--brand-primary); margin-bottom: 4px;">
                Create Your <span style="color: var(--brand-accent);">Account</span>
            </div>
            <p style="font-size: 14px; color: var(--text-muted);">Join our verified resource donation and assistance community.</p>
        </div>

        <form action="{{ route('register.submit') }}" method="POST">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Maria Santos">
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="username">Username (Optional)</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="e.g. msantos">
                    @error('username')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address <span style="color:var(--danger)">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. maria@example.com">
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="phone">Phone / Mobile Number <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="e.g. +63 917 123 4567">
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City / Municipality</label>
                    <input type="text" id="city" name="city" value="{{ old('city') }}" placeholder="e.g. Makati City">
                    @error('city')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="province">Province / Region</label>
                    <input type="text" id="province" name="province" value="{{ old('province') }}" placeholder="e.g. Metro Manila">
                    @error('province')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span style="color:var(--danger)">*</span> (Min. 8 characters)</label>
                    <input type="password" id="password" name="password" required placeholder="Choose a strong password">
                    @error('password')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password <span style="color:var(--danger)">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter password">
                </div>
            </div>

            <div class="form-group" style="margin-top: 8px;">
                <p style="font-size: 12px; color: var(--text-muted);">
                    By registering, you agree to our physical resource sharing terms, verified donation guidelines, and privacy principles.
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                Create Member Account
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center; font-size: 13px;">
            Already have an account? <a href="{{ route('login') }}" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>
@endsection
