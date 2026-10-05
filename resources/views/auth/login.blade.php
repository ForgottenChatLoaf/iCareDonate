@extends('layouts.public')

@section('title', 'Sign In')

@section('content')
<div class="container container-auth">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-family: var(--font-heading); font-size: 28px; color: var(--brand-primary); margin-bottom: 4px;">
                Welcome <span style="color: var(--brand-accent);">Back</span>
            </div>
            <p style="font-size: 14px; color: var(--text-muted);">Sign in to access your donation dashboard or admin panel</p>
        </div>

        <form action="{{ route('login.submit') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="e.g. maria@example.com or admin@icaredonate.org">
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="margin-bottom: 0;">Password</label>
                    <a href="{{ route('password.request') }}" style="font-size: 12px;">Forgot password?</a>
                </div>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <button type="button" class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 12px; font-weight: 600;">Show</button>
                </div>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} style="width: auto;">
                <label for="remember" style="font-size: 13px; font-weight: 400; margin: 0; cursor: pointer;">Remember me on this browser</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                Sign In
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center; font-size: 13px;">
            Don't have an account yet? <a href="{{ route('register') }}" style="font-weight: 600;">Register as a community member</a>
        </div>

        <!-- Quick Demo Credentials Box -->
        <div style="margin-top: 20px; background: var(--surface-soft); border: 1px dashed var(--border-strong); border-radius: var(--radius-md); padding: 14px; font-size: 12px;">
            <div style="font-weight: 700; color: var(--brand-primary); margin-bottom: 6px;">Demo Test Credentials:</div>
            <div><strong>Administrator:</strong> <code>admin@icaredonate.org</code> / <code>password123</code></div>
            <div style="margin-top: 3px;"><strong>Donor Member:</strong> <code>maria@example.com</code> / <code>password123</code></div>
            <div style="margin-top: 3px;"><strong>Requester Member:</strong> <code>juan@example.com</code> / <code>password123</code></div>
        </div>
    </div>
</div>
@endsection
