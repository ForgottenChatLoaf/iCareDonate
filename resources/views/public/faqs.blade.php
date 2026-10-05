@extends('layouts.public')

@section('title', 'Frequently Asked Questions')

@section('content')
<div class="container" style="padding: 50px 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 40px;">
            <span class="status-pill status-info" style="margin-bottom: 8px;">
                <x-icon name="help-circle" size="14" /> Help Center
            </span>
            <h1>Frequently Asked Questions</h1>
            <p class="lead" style="margin: 0 auto;">Common questions about our physical donation pipeline, verification checks, and claim codes.</p>
        </div>

        @forelse($faqs as $categoryName => $faqGroup)
            <div style="margin-bottom: 36px;">
                <h3 style="color: var(--brand-primary); border-bottom: 2px solid var(--border); padding-bottom: 8px; margin-bottom: 16px;">
                    {{ $categoryName }}
                </h3>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    @foreach($faqGroup as $faq)
                        <div class="card" style="padding: 20px;">
                            <h4 style="color: var(--text); font-family: var(--font-body); font-weight: 700; margin-bottom: 8px;">
                                Q: {{ $faq->question }}
                            </h4>
                            <p style="font-size: 14px; color: var(--text-soft); margin: 0; line-height: 1.6;">
                                {{ $faq->answer }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card" style="text-align: center; padding: 40px;">
                <p>No questions listed yet. Feel free to contact our team directly.</p>
                <a href="{{ route('contact') }}" class="btn btn-primary btn-sm" style="margin-top: 10px;">Contact Support</a>
            </div>
        @endforelse

        <div class="card" style="text-align: center; padding: 32px; background: var(--surface-soft); margin-top: 40px;">
            <h3>Still have questions?</h3>
            <p>Our community administrators are available to help you navigate donations and claims.</p>
            <a href="{{ route('contact') }}" class="btn btn-outline btn-sm" style="margin-top: 10px;">Reach Out to Our Team</a>
        </div>
    </div>
</div>
@endsection
