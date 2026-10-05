@extends('layouts.public')

@section('title', 'How It Works — Step-by-Step Lifecycle')

@section('content')
<div class="container" style="padding: 50px 20px;">
    <div style="max-width: 860px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 48px;">
            <span class="status-pill status-approved" style="margin-bottom: 12px;">Lifecycle Workflow</span>
            <h1 style="font-size: 2.8rem;">How iCareDonate Works</h1>
            <p class="lead" style="margin: 0 auto;">A seamless, verified journey from generous offer to delivered community impact.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 32px;">
            <!-- Step 1 -->
            <div class="card" style="display: grid; grid-template-columns: 80px 1fr; gap: 24px; align-items: start;">
                <div style="width: 70px; height: 70px; border-radius: 16px; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    1
                </div>
                <div>
                    <span class="status-pill status-pending" style="margin-bottom: 8px;">Step 1: Donation Submission</span>
                    <h3 style="font-size: 1.5rem; color: var(--brand-primary);">Donor Posts Physical Resource</h3>
                    <p>
                        A donor signs in and submits details about the physical items they wish to share: category, quantity, condition (New, Like New, Good, Fair), expiration date (for food), pickup address, and photos.
                    </p>
                    <div style="background: var(--surface-soft); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px;">
                        <strong>Key Status:</strong> <span class="status-pill status-pending">Pending Verification</span>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="card" style="display: grid; grid-template-columns: 80px 1fr; gap: 24px; align-items: start;">
                <div style="width: 70px; height: 70px; border-radius: 16px; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    2
                </div>
                <div>
                    <span class="status-pill status-verified" style="margin-bottom: 8px;">Step 2: Administrative Verification</span>
                    <h3 style="font-size: 1.5rem; color: var(--brand-primary);">Platform Safety &amp; Quality Review</h3>
                    <p>
                        iCareDonate administrators inspect the submitted donation details and media. If item conditions meet safety criteria, it is approved. The donation becomes publicly available for matching.
                    </p>
                    <div style="background: var(--surface-soft); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px;">
                        <strong>Key Status:</strong> <span class="status-pill status-approved">Approved &amp; Available</span>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="card" style="display: grid; grid-template-columns: 80px 1fr; gap: 24px; align-items: start;">
                <div style="width: 70px; height: 70px; border-radius: 16px; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    3
                </div>
                <div>
                    <span class="status-pill status-info" style="margin-bottom: 8px;">Step 3: Verified Request Submission</span>
                    <h3 style="font-size: 1.5rem; color: var(--brand-primary);">Requester Applies with Supporting Proof</h3>
                    <p>
                        A community member or representative in need requests an available resource, specifying the exact quantity needed, urgency level, reason, and uploads supporting proof (e.g. Barangay Indigency, Doctor's referral, Student ID).
                    </p>
                    <div style="background: var(--surface-soft); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px;">
                        <strong>Key Status:</strong> <span class="status-pill status-pending">Proof Review</span>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="card" style="display: grid; grid-template-columns: 80px 1fr; gap: 24px; align-items: start;">
                <div style="width: 70px; height: 70px; border-radius: 16px; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    4
                </div>
                <div>
                    <span class="status-pill status-matched" style="margin-bottom: 8px;">Step 4: Matching &amp; Transaction Generation</span>
                    <h3 style="font-size: 1.5rem; color: var(--brand-primary);">Admin Allocates Resources &amp; Issues Claim Code</h3>
                    <p>
                        The administrator reviews the proof and pairs the request with available stock. A unique Claim Code (e.g. <code>CLAIM-XYZ123</code>) and pickup schedule are generated. Item quantities are reserved in MySQL so they cannot be double-claimed.
                    </p>
                    <div style="background: var(--surface-soft); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px;">
                        <strong>Key Status:</strong> <span class="status-pill status-matched">Matched</span> &middot; <span class="status-pill status-pending">Pending Claim</span>
                    </div>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="card" style="display: grid; grid-template-columns: 80px 1fr; gap: 24px; align-items: start;">
                <div style="width: 70px; height: 70px; border-radius: 16px; background: var(--brand-primary-light); color: var(--brand-primary-dark); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    5
                </div>
                <div>
                    <span class="status-pill status-approved" style="margin-bottom: 8px;">Step 5: Physical Handoff &amp; Completion</span>
                    <h3 style="font-size: 1.5rem; color: var(--brand-primary);">Claim Verification &amp; History Update</h3>
                    <p>
                        The beneficiary presents the claim code at the designated center or pickup point. Upon receipt, the donor or administrator marks the transaction completed. Quantities shift to claimed, notifications are dispatched, and the audit history is finalized.
                    </p>
                    <div style="background: var(--surface-soft); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px;">
                        <strong>Key Status:</strong> <span class="status-pill status-approved">Completed</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 48px;">
            <a href="{{ route('donations.create') }}" class="btn btn-accent btn-lg">+ Start by Posting a Donation</a>
            <a href="{{ route('donations.index') }}" class="btn btn-primary btn-lg" style="margin-left: 12px;">Browse Available Items</a>
        </div>
    </div>
</div>
@endsection
