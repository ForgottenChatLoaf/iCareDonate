@extends('layouts.public')

@section('title', 'Contact & Community Assistance')

@section('content')
<div class="container" style="padding: 50px 20px;">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 40px;">
            <span class="status-pill status-info" style="margin-bottom: 8px;">
                <x-icon name="mail" size="14" /> Support Center
            </span>
            <h1>Get In Touch With iCareDonate</h1>
            <p class="lead" style="margin: 0 auto;">Have questions about dropping off physical items, institutional partnerships, or verified community requests?</p>
        </div>

        <div class="grid-2">
            <!-- Contact Info -->
            <div class="card" style="background: var(--surface-soft);">
                <h3 style="color: var(--brand-primary); margin-bottom: 16px;">Central Headquarters</h3>
                <p>We work in direct coordination with barangay halls, school districts, and disaster evacuation centers.</p>

                <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 24px;">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <x-icon name="map-pin" size="22" style="color: var(--brand-primary);" />
                        <div>
                            <div style="font-weight: 700; font-size: 13px;">Drop-off &amp; Logistics Hub</div>
                            <div style="font-size: 13px; color: var(--text-soft);">Makati Community Logistics Center, Metro Manila</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <x-icon name="phone" size="22" style="color: var(--brand-primary);" />
                        <div>
                            <div style="font-weight: 700; font-size: 13px;">Emergency Hotline</div>
                            <div style="font-size: 13px; color: var(--text-soft);">+63 917 123 4567 / (02) 8123-4567</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <x-icon name="mail" size="22" style="color: var(--brand-primary);" />
                        <div>
                            <div style="font-weight: 700; font-size: 13px;">Official Email</div>
                            <div style="font-size: 13px; color: var(--text-soft);">help@icaredonate.org / support@icaredonate.local</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <x-icon name="clock" size="22" style="color: var(--brand-primary);" />
                        <div>
                            <div style="font-weight: 700; font-size: 13px;">Operating Hours</div>
                            <div style="font-size: 13px; color: var(--text-soft);">Monday to Saturday, 8:00 AM – 5:00 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inquiry Form -->
            <div class="card">
                <h3 style="margin-bottom: 16px;">Send Us a Message</h3>
                <form action="{{ route('contact.submit') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Your Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required placeholder="e.g. Elena Ramos">
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span style="color:var(--danger)">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required placeholder="e.g. elena@example.com">
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="category">Inquiry Subject Category <span style="color:var(--danger)">*</span></label>
                        <select id="category" name="category" required>
                            <option value="donation">Offering Bulk / Physical Donation</option>
                            <option value="request">Assistance &amp; Resource Request Verification</option>
                            <option value="partnership">Institutional Partnership</option>
                            <option value="technical">Platform Issue / Account Support</option>
                            <option value="general">General Question</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject Title <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="Brief summary of your inquiry">
                        @error('subject')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="message">Message Details <span style="color:var(--danger)">*</span></label>
                        <textarea id="message" name="message" rows="4" required placeholder="How can our community team assist you?">{{ old('message') }}</textarea>
                        @error('message')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <x-icon name="mail" size="16" /> Submit Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
