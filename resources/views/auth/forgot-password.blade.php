@extends('layouts.public')

@section('title', 'Password Recovery')

@section('content')
<div class="container container-auth">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-family: var(--font-heading); font-size: 26px; color: var(--brand-primary); margin-bottom: 4px;">
                Reset Your <span style="color: var(--brand-accent);">Password</span>
            </div>
            <p style="font-size: 14px; color: var(--text-muted);">Enter your registered email address to receive password reset instructions.</p>
        </div>

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Registered Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. maria@example.com">
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                Send Recovery Link
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center; font-size: 13px;">
            Remembered your credentials? <a href="{{ route('login') }}" style="font-weight: 600;">Back to Sign In</a>
        </div>
    </div>
</div>
@endsection
