@extends('layouts.admin')

@section('title', 'Admin — Assess Request #' . $request->id)

@section('content')
<div style="max-width: 960px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="status-pill status-{{ $request->status }}" style="margin-bottom: 6px;">
                Status: {{ ucfirst(str_replace('_', ' ', $request->status)) }}
            </span>
            <h1>Assess Request #{{ $request->id }}</h1>
            <p>{{ $request->item_name }} &middot; Requested by {{ $request->requester->name }}</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline">&larr; Back to Requests</a>
            @if(in_array($request->status, ['approved', 'matched']))
                <a href="{{ route('admin.matches.index') }}" class="btn btn-accent">
                    <x-icon name="repeat" size="14" /> Open Matching
                </a>
            @endif
        </div>
    </div>

    @if($request->status === 'approved')
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <span><x-icon name="check-circle" size="16" /> Request approved and active in the Matching Pool. Ready for stock allocation.</span>
        </div>
    @elseif($request->status === 'rejected')
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <span><x-icon name="alert" size="16" /> Request declined. Reason: <em>{{ $request->rejection_reason }}</em></span>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 24px;">
        <!-- Left: Application Details & Proofs -->
        <div>
            <div class="card" style="margin-bottom: 24px;">
                <h3 style="color: var(--brand-primary); margin-bottom: 12px;">Need Justification &amp; Purpose</h3>
                <div style="background: var(--surface-soft); padding: 18px; border-radius: var(--radius-md); font-size: 14px; line-height: 1.7; margin-bottom: 18px;">
                    {{ $request->purpose }}
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 13px;">
                    <div>
                        <span style="color: var(--text-muted); display: block;">Quantity Requested:</span>
                        <strong style="font-size: 16px; color: var(--text);">{{ $request->quantity_requested }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Urgency Priority:</span>
                        <span class="status-pill status-{{ $request->urgency_level === 'emergency' ? 'rejected' : 'info' }}">
                            {{ ucfirst($request->urgency_level) }}
                        </span>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Contact Person:</span>
                        <strong>{{ $request->requester->name }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block;">Phone Number:</span>
                        <strong>{{ $request->contact_number }}</strong>
                    </div>
                    <div style="grid-column: span 2;">
                        <span style="color: var(--text-muted); display: block;">Delivery Address:</span>
                        <strong>{{ $request->delivery_address }}</strong>
                    </div>
                </div>
            </div>

            <!-- Supporting Proof Files -->
            <div class="card">
                <h3 style="margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="file-text" size="18" style="color: var(--brand-primary);" /> Supporting Verification Documents
                </h3>

                @if($request->proofs->isEmpty())
                    <p style="color: var(--text-muted); font-size: 13px;">No supporting documents uploaded by applicant.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($request->proofs as $proof)
                            <div style="padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--surface-soft); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;">{{ $proof->file_name }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Uploaded {{ $proof->created_at->format('M d, Y') }}</div>
                                </div>
                                <a href="{{ $proof->url }}" target="_blank" class="btn btn-outline btn-sm">
                                    <x-icon name="eye" size="14" /> View Proof
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Action & Interview Decisions -->
        <div>
            <div class="card" style="padding: 24px; margin-bottom: 24px; border: 2px solid var(--border-strong);">
                <h3 style="margin-bottom: 16px;">Application Assessment</h3>

                <form action="{{ route('admin.requests.approve', $request->id) }}" method="POST" style="margin-bottom: 18px;">
                    @csrf
                    
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                            <input type="checkbox" name="interview_required" value="1" {{ $request->interview_required ? 'checked' : '' }} style="width: auto;" onchange="document.getElementById('interview-reason-box').style.display = this.checked ? 'block' : 'none'">
                            Schedule Verification Interview First
                        </label>
                    </div>

                    <div id="interview-reason-box" style="display: {{ $request->interview_required ? 'block' : 'none' }}; margin-bottom: 14px;">
                        <label for="interview_reason" style="font-size: 12px;">Reason / Assessment Questions:</label>
                        <textarea id="interview_reason" name="interview_reason" rows="2" placeholder="e.g. Verify pupil masterlist with tutorial center director" style="font-size: 13px;">{{ $request->interview_reason }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="admin_notes" style="font-size: 12px;">Internal Admin Notes</label>
                        <input type="text" id="admin_notes" name="admin_notes" value="{{ $request->admin_notes }}" placeholder="e.g. Verified with barangay certificate" style="font-size: 13px;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                        <x-icon name="check-circle" size="16" /> Approve &amp; Queue for Allocation
                    </button>
                </form>

                <!-- Rejection Form -->
                <div style="padding-top: 14px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-outline btn-sm" style="width: 100%; color: var(--danger); border-color: var(--danger);" onclick="document.getElementById('req-reject-box').style.display = document.getElementById('req-reject-box').style.display === 'none' ? 'block' : 'none'">
                        ⚠️ Reject Application
                    </button>
                    <div id="req-reject-box" style="display: none; background: var(--danger-bg); padding: 14px; border-radius: var(--radius-md); margin-top: 10px;">
                        <form action="{{ route('admin.requests.reject', $request->id) }}" method="POST">
                            @csrf
                            <label for="rejection_reason" style="font-size: 12px; color: var(--danger);">Rejection Explanation <span style="color:var(--danger)">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="e.g. Incomplete proof documentation or duplicate application." style="font-size: 13px;"></textarea>
                            <button type="submit" class="btn btn-danger btn-sm" style="margin-top: 8px; width: 100%;">Confirm Rejection</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
