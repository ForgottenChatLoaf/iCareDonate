@extends('layouts.public')

@section('title', 'Donation Categories')

@section('content')
<div class="container" style="padding: 50px 20px;">
    <div style="text-align: center; max-width: 640px; margin: 0 auto 40px;">
        <span class="status-pill status-info" style="margin-bottom: 8px;">
            <x-icon name="tag" size="14" /> Resource Directory
        </span>
        <h1>Donation Categories</h1>
        <p>Explore physical resources sorted by impact domain. Every item category is verified by our inspection team.</p>
    </div>

    <div class="grid-3">
        @foreach($categories as $category)
            <div class="card" style="display: flex; flex-direction: column;">
                <div style="color: var(--brand-primary); margin-bottom: 12px;">
                    @switch($category->slug)
                        @case('food') <x-icon name="utensils" size="36" /> @break
                        @case('school-supplies') <x-icon name="book-open" size="36" /> @break
                        @case('clothing') <x-icon name="shirt" size="36" /> @break
                        @case('medical') <x-icon name="activity" size="36" /> @break
                        @case('household') <x-icon name="home" size="36" /> @break
                        @case('electronics') <x-icon name="laptop" size="36" /> @break
                        @default <x-icon name="package" size="36" />
                    @endswitch
                </div>
                <h3 style="color: var(--brand-primary); margin-bottom: 8px;">{{ $category->name }}</h3>
                <p style="font-size: 13px; color: var(--text-soft); flex: 1;">
                    {{ $category->description ?? 'Verified community physical donations.' }}
                </p>
                <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <span class="status-pill status-approved">{{ $category->donations_count }} Available</span>
                    <a href="{{ route('donations.index', ['category' => $category->id]) }}" class="btn btn-outline btn-sm">
                        View Listings &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
