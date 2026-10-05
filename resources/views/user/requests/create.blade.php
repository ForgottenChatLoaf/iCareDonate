@extends('layouts.user')

@section('title', 'Request a Resource')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span class="status-pill status-info" style="margin-bottom: 6px;">Beneficiary Application</span>
        <h1>Submit Resource Request</h1>
        <p>Apply for verified community assistance. All requests undergo administrative review and proof validation.</p>
    </div>

    @if($donation)
        <div class="card" style="margin-bottom: 24px; background: var(--surface-soft); border-left: 4px solid var(--brand-primary); padding: 18px 24px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--brand-primary); text-transform: uppercase;">Requesting From Available Donation:</div>
            <h3 style="margin: 4px 0 6px;">{{ $donation->title }}</h3>
            <div style="font-size: 13px; color: var(--text-soft);">
                Category: <strong>{{ $donation->category->name }}</strong> &middot; Available Stock: <strong>{{ $donation->quantity_available }} {{ $donation->unit }}</strong> &middot; Donor: {{ $donation->donor->name }}
            </div>
        </div>
    @endif

    <div class="card" style="padding: 32px;">
        <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if($donation)
                <input type="hidden" name="donation_id" value="{{ $donation->id }}">
            @endif

            <div class="form-group">
                <label for="item_name">Requested Item / Resource Name <span style="color:var(--danger)">*</span></label>
                <input type="text" id="item_name" name="item_name" value="{{ old('item_name', $donation ? $donation->title : '') }}" required placeholder="e.g. 10 Elementary School Supply Packs">
                @error('item_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity_requested">
                        Quantity Needed <span style="color:var(--danger)">*</span>
                        @if($donation)
                            <span style="font-size:11px;color:var(--text-muted);font-weight:normal;">(Max: {{ $donation->quantity_available }})</span>
                        @endif
                    </label>
                    <input type="number" id="quantity_requested" name="quantity_requested" min="1" max="{{ $donation ? $donation->quantity_available : 500 }}" value="{{ old('quantity_requested', 1) }}" required>
                    @error('quantity_requested')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="urgency_level">Urgency Level <span style="color:var(--danger)">*</span></label>
                    <select id="urgency_level" name="urgency_level" required>
                        <option value="low" {{ old('urgency_level') == 'low' ? 'selected' : '' }}>Low (General ongoing need)</option>
                        <option value="medium" {{ old('urgency_level', 'medium') == 'medium' ? 'selected' : '' }}>Medium (Needed within 2-3 weeks)</option>
                        <option value="high" {{ old('urgency_level') == 'high' ? 'selected' : '' }}>High (Needed within 3-5 days)</option>
                        <option value="emergency" {{ old('urgency_level') == 'emergency' ? 'selected' : '' }}>Emergency / Calamity Relief</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="purpose">Reason &amp; Purpose of Need <span style="color:var(--danger)">*</span></label>
                <textarea id="purpose" name="purpose" rows="4" required placeholder="Describe who will benefit from this item (e.g. students, families, patients) and explain your current circumstances to help administrators verify priority.">{{ old('purpose') }}</textarea>
                @error('purpose')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Primary Contact Number <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number', auth()->user()->phone) }}" required placeholder="e.g. +63 918 876 5432">
                    @error('contact_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="delivery_address">Delivery / Beneficiary Address <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="delivery_address" name="delivery_address" value="{{ old('delivery_address', auth()->user()->address) }}" required placeholder="Full street address and city">
                    @error('delivery_address')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Supporting Proof Upload -->
            <div style="margin: 24px 0 16px; padding: 18px; background: var(--surface-soft); border-radius: var(--radius-md); border: 1px dashed var(--border-strong);">
                <label for="proofs" style="font-size: 14px; color: var(--brand-primary); margin-bottom: 4px;">
                    📎 Supporting Proof &amp; Documents (Required for Verification)
                </label>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                    Upload valid proof such as Barangay Certificate of Indigency, Student Enrollment Slip, Medical Prescription/Doctor's Note, or Displaced Family Card. (Max 5MB each, PDF or Image).
                </p>
                <input type="file" id="proofs" name="proofs[]" multiple accept="image/*,.pdf">
                @error('proofs.*')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-accent btn-lg" style="flex: 1;">
                    Submit Application for Review
                </button>
                <a href="{{ route('donations.index') }}" class="btn btn-outline btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
