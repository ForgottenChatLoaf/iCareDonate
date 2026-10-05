@extends('layouts.admin')

@section('title', 'Admin — FAQ Management')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-info" style="margin-bottom: 6px;">
        <x-icon name="help-circle" size="14" /> Public Knowledge Base
    </span>
    <h1>FAQ Management</h1>
    <p>Maintain frequently asked questions to guide donors and beneficiaries across the platform.</p>
</div>

<div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 24px;">
    <!-- Add FAQ Form -->
    <div class="card" style="padding: 24px; align-self: start;">
        <h3 style="color: var(--brand-primary); margin-bottom: 16px;">+ Add New Question</h3>
        
        <form action="{{ route('admin.faqs.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="category">Category <span style="color:var(--danger)">*</span></label>
                <input type="text" id="category" name="category" required placeholder="e.g. Donations, Claims, General">
            </div>

            <div class="form-group">
                <label for="question">Question <span style="color:var(--danger)">*</span></label>
                <input type="text" id="question" name="question" required placeholder="e.g. How do I verify my donation?">
            </div>

            <div class="form-group">
                <label for="answer">Answer <span style="color:var(--danger)">*</span></label>
                <textarea id="answer" name="answer" rows="4" required placeholder="Detailed response for users"></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Display Order</label>
                <input type="number" id="sort_order" name="sort_order" value="1">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create FAQ Entry</button>
        </form>
    </div>

    <!-- FAQs Directory -->
    <div class="card">
        <div class="card-header">
            <h3>Published FAQs</h3>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            @foreach($faqs as $faq)
                <div style="padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--surface-soft);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <span class="status-pill status-info" style="font-size: 11px;">{{ $faq->category }}</span>
                        <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" onsubmit="return confirm('Delete this FAQ question?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--danger); font-size: 12px;">Delete</button>
                        </form>
                    </div>
                    <h4 style="font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 6px;">Q: {{ $faq->question }}</h4>
                    <p style="font-size: 13px; color: var(--text-soft); margin: 0; line-height: 1.6;">{{ $faq->answer }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
