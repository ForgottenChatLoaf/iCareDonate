@extends('layouts.admin')

@section('title', 'Admin — Manage Resource Categories')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-approved" style="margin-bottom: 6px;">
        <x-icon name="tag" size="14" /> Taxonomy
    </span>
    <h1>Resource Categories</h1>
    <p>Define resource types, manage classifications, and monitor associated active donation inventory.</p>
</div>

<div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 24px;">
    <!-- Add Category Form -->
    <div class="card" style="padding: 24px; align-self: start;">
        <h3 style="color: var(--brand-primary); margin-bottom: 16px;">+ Add New Category</h3>
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Category Name <span style="color:var(--danger)">*</span></label>
                <input type="text" id="name" name="name" required placeholder="e.g. Sports Equipment">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Summary of items in this category"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="icon">Icon Name</label>
                    <input type="text" id="icon" name="icon" value="package" placeholder="e.g. package, heart, laptop">
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="10">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Category</button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="card">
        <div class="card-header">
            <h3>Active Categories Directory</h3>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Donations</th>
                        <th>Requests</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $cat)
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text);">{{ $cat->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ Str::limit($cat->description, 40) }}</div>
                            </td>
                            <td><strong>{{ $cat->donations_count }}</strong></td>
                            <td>{{ $cat->requests_count }}</td>
                            <td>
                                <span class="status-pill status-{{ $cat->is_active ? 'approved' : 'rejected' }}">
                                    {{ $cat->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td>
                                @if($cat->donations_count == 0)
                                    <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this unused category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 11px; color: var(--text-muted);">In use</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
