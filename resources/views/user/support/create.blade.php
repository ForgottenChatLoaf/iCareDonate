@extends('layouts.user')

@section('title', 'Submit a Support Concern')

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span class="status-pill status-info" style="margin-bottom: 6px;">Assistance &amp; Support</span>
        <h1>Submit a Concern or Inquiry</h1>
        <p>Our administrator team is here to assist with resource verification, claim difficulties, or general guidance.</p>
    </div>

    <div class="card" style="padding: 32px;">
        <form action="{{ route('support.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="category">Concern Category <span style="color:var(--danger)">*</span></label>
                <select id="category" name="category" required>
                    <option value="donation">Donation Verification / Listing Issue</option>
                    <option value="request">Request Status / Proof Validation</option>
                    <option value="claim">Claim Code / Pickup Handoff Dispute</option>
                    <option value="account">Account Profile / Password Help</option>
                    <option value="general">General Feedback / Platform Question</option>
                </select>
                @error('category')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="subject">Subject Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Question regarding Claim Code schedule">
                @error('subject')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="message">Detailed Description <span style="color:var(--danger)">*</span></label>
                <textarea id="message" name="message" rows="5" required placeholder="Please provide clear details, dates, or relevant transaction numbers so we can resolve your issue quickly.">{{ old('message') }}</textarea>
                @error('message')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                    <x-icon name="mail" size="16" /> Send Concern to Support Team
                </button>
                <a href="{{ route('support.index') }}" class="btn btn-outline btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
