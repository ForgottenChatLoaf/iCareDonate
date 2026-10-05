@extends('layouts.user')

@section('title', 'Edit Donation — ' . $donation->title)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span class="status-pill status-info" style="margin-bottom: 6px;">Modify Submission</span>
        <h1>Edit Donation Offer</h1>
        <p>Update specifications, quantity, or logistics. Resubmitting will send the item for review.</p>
    </div>

    <div class="card" style="padding: 32px;">
        <form action="{{ route('donations.update', $donation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Donation Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $donation->title) }}" required>
                @error('title')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Resource Category <span style="color:var(--danger)">*</span></label>
                    <select id="category_id" name="category_id" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $donation->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="condition_type">Physical Condition <span style="color:var(--danger)">*</span></label>
                    <select id="condition_type" name="condition_type" required>
                        <option value="new" {{ old('condition_type', $donation->condition_type) == 'new' ? 'selected' : '' }}>Brand New</option>
                        <option value="like_new" {{ old('condition_type', $donation->condition_type) == 'like_new' ? 'selected' : '' }}>Like New</option>
                        <option value="good" {{ old('condition_type', $donation->condition_type) == 'good' ? 'selected' : '' }}>Good</option>
                        <option value="fair" {{ old('condition_type', $donation->condition_type) == 'fair' ? 'selected' : '' }}>Fair</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity_total">Quantity Total <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="quantity_total" name="quantity_total" min="1" value="{{ old('quantity_total', $donation->quantity_total) }}" required>
                </div>

                <div class="form-group">
                    <label for="unit">Unit <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="unit" name="unit" value="{{ old('unit', $donation->unit) }}" required>
                </div>

                <div class="form-group">
                    <label for="expiration_date">Expiration Date</label>
                    <input type="date" id="expiration_date" name="expiration_date" value="{{ old('expiration_date', $donation->expiration_date ? $donation->expiration_date->format('Y-m-d') : '') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description <span style="color:var(--danger)">*</span></label>
                <textarea id="description" name="description" rows="4" required>{{ old('description', $donation->description) }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_method">Handover Method <span style="color:var(--danger)">*</span></label>
                    <select id="pickup_method" name="pickup_method" required>
                        <option value="pickup" {{ old('pickup_method', $donation->pickup_method) == 'pickup' ? 'selected' : '' }}>Pickup</option>
                        <option value="delivery" {{ old('pickup_method', $donation->pickup_method) == 'delivery' ? 'selected' : '' }}>Delivery</option>
                        <option value="both" {{ old('pickup_method', $donation->pickup_method) == 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pickup_schedule">Available Schedule</label>
                    <input type="text" id="pickup_schedule" name="pickup_schedule" value="{{ old('pickup_schedule', $donation->pickup_schedule) }}">
                </div>
            </div>

            <div class="form-group">
                <label for="pickup_address">Pickup Address <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pickup_address" name="pickup_address" value="{{ old('pickup_address', $donation->pickup_address) }}" required>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number <span style="color:var(--danger)">*</span></label>
                <input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number', $donation->contact_number) }}" required>
            </div>

            <div class="form-group">
                <label for="images">Add More Photos</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">Save Changes &amp; Resubmit</button>
                <a href="{{ route('donations.preview', $donation->id) }}" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
