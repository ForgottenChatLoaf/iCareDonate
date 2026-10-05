@extends('layouts.user')

@section('title', 'Post a Physical Resource Donation')

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span class="status-pill status-approved" style="margin-bottom: 6px;">New Contribution</span>
        <h1>Post a Physical Donation</h1>
        <p>Offer tangible essentials to verified communities. Your offer will be submitted for safety &amp; quality verification.</p>
    </div>

    <div class="card" style="padding: 32px;">
        <form action="{{ route('donations.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Section 1: Item Basic Info -->
            <div style="font-size: 16px; font-weight: 700; color: var(--brand-primary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                1. Item Information
            </div>

            <div class="form-group">
                <label for="title">Donation Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. 50 Sets of Elementary School Supply Backpacks">
                @error('title')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Resource Category <span style="color:var(--danger)">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="condition_type">Physical Condition <span style="color:var(--danger)">*</span></label>
                    <select id="condition_type" name="condition_type" required>
                        <option value="new" {{ old('condition_type') == 'new' ? 'selected' : '' }}>Brand New (Unopened/Sealed)</option>
                        <option value="like_new" {{ old('condition_type') == 'like_new' ? 'selected' : '' }}>Like New (Barely used, clean)</option>
                        <option value="good" {{ old('condition_type', 'good') == 'good' ? 'selected' : '' }}>Good (Usable, fully functional)</option>
                        <option value="fair" {{ old('condition_type') == 'fair' ? 'selected' : '' }}>Fair (Minor wear, functional)</option>
                    </select>
                    @error('condition_type')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity_total">Quantity Total <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="quantity_total" name="quantity_total" min="1" max="10000" value="{{ old('quantity_total', 1) }}" required>
                    @error('quantity_total')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="unit">Unit of Measure <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="unit" name="unit" value="{{ old('unit', 'pcs') }}" required placeholder="e.g. pcs, sets, boxes, sacks, kg">
                    @error('unit')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="expiration_date">Expiration Date (If Food/Perishable)</label>
                    <input type="date" id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}">
                    @error('expiration_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="description">Detailed Description &amp; Item Features <span style="color:var(--danger)">*</span></label>
                <textarea id="description" name="description" rows="4" required placeholder="Specify what is included in the package, sizes, brand, contents, and suitability for beneficiaries.">{{ old('description') }}</textarea>
                @error('description')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <!-- Section 2: Media Upload -->
            <div style="font-size: 16px; font-weight: 700; color: var(--brand-primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                2. Item Photos
            </div>

            <div class="form-group">
                <label for="images">Upload Photos of Item (Max 5MB each)</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*" data-preview="img-preview-box">
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                    Clear photos help administrators verify condition faster.
                </div>
                <div style="margin-top: 12px;">
                    <img id="img-preview-box" src="" alt="Preview" style="display: none; max-height: 180px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                </div>
            </div>

            <!-- Section 3: Logistics & Pickup -->
            <div style="font-size: 16px; font-weight: 700; color: var(--brand-primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                3. Pickup &amp; Logistics Details
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_method">Handover / Delivery Method <span style="color:var(--danger)">*</span></label>
                    <select id="pickup_method" name="pickup_method" required>
                        <option value="pickup" {{ old('pickup_method') == 'pickup' ? 'selected' : '' }}>Requester Pickup at Address</option>
                        <option value="delivery" {{ old('pickup_method') == 'delivery' ? 'selected' : '' }}>Donor Will Deliver</option>
                        <option value="both" {{ old('pickup_method', 'both') == 'both' ? 'selected' : '' }}>Flexible (Either Pickup or Delivery)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pickup_schedule">Available Schedule / Hours</label>
                    <input type="text" id="pickup_schedule" name="pickup_schedule" value="{{ old('pickup_schedule') }}" placeholder="e.g. Mon-Fri 9:00 AM - 4:00 PM">
                </div>
            </div>

            <div class="form-group">
                <label for="pickup_address">Pickup / Dispatch Address <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pickup_address" name="pickup_address" value="{{ old('pickup_address', auth()->user()->address) }}" required placeholder="Building, Street, Landmark">
                @error('pickup_address')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_barangay">Barangay</label>
                    <input type="text" id="pickup_barangay" name="pickup_barangay" value="{{ old('pickup_barangay', auth()->user()->barangay) }}" placeholder="e.g. Bel-Air">
                </div>

                <div class="form-group">
                    <label for="pickup_city">City / Municipality</label>
                    <input type="text" id="pickup_city" name="pickup_city" value="{{ old('pickup_city', auth()->user()->city) }}" placeholder="e.g. Makati City">
                </div>

                <div class="form-group">
                    <label for="pickup_province">Province / Region</label>
                    <input type="text" id="pickup_province" name="pickup_province" value="{{ old('pickup_province', auth()->user()->province) }}" placeholder="e.g. Metro Manila">
                </div>
            </div>

            <div class="form-group">
                <label for="pickup_notes">Special Handling / Gate Instructions</label>
                <textarea id="pickup_notes" name="pickup_notes" rows="2" placeholder="e.g. Inform guard desk at entrance, heavy items need truck loading.">{{ old('pickup_notes') }}</textarea>
            </div>

            <!-- Section 4: Contact Verification -->
            <div style="font-size: 16px; font-weight: 700; color: var(--brand-primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                4. Donor Contact Information
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="donor_name">Contact Person Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="donor_name" name="donor_name" value="{{ old('donor_name', auth()->user()->name) }}" required>
                    @error('donor_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="contact_number">Mobile / Phone Number <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number', auth()->user()->phone) }}" required>
                    @error('contact_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-accent btn-lg" style="flex: 1;">
                    Submit Donation for Verification
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
