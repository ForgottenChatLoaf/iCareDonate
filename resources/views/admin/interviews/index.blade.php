@extends('layouts.admin')

@section('title', 'Admin — Verification Interviews')

@section('content')
<div style="margin-bottom: 24px;">
    <span class="status-pill status-info" style="margin-bottom: 6px;">
        <x-icon name="mic" size="14" /> Assessment Protocols
    </span>
    <h1>Verification Interviews</h1>
    <p>Schedule and document one-on-one assessments for high-urgency or complex community assistance requests.</p>
</div>

<!-- Schedule Interview Card -->
<div class="card" style="padding: 28px; margin-bottom: 32px; border: 1px solid var(--border-strong);">
    <h3 style="color: var(--brand-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <x-icon name="calendar" size="18" /> Schedule Assessment Interview
    </h3>

    <form action="{{ route('admin.interviews.store') }}" method="POST">
        @csrf

        <div class="form-row" style="margin-bottom: 16px;">
            <div class="form-group" style="flex: 1;">
                <label for="request_id">Associated Resource Request</label>
                <select id="request_id" name="request_id">
                    <option value="">-- Optional / Select Flagged Request --</option>
                    @foreach($eligibleRequests as $er)
                        <option value="{{ $er->id }}" data-user="{{ $er->requester_id }}">
                            #{{ $er->id }} — {{ $er->item_name }} ({{ $er->requester->name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="flex: 1;">
                <label for="user_id">Beneficiary / User ID <span style="color:var(--danger)">*</span></label>
                <select id="user_id" name="user_id" required>
                    <option value="">-- Select Community Member --</option>
                    @foreach(\App\Models\User::where('role', 'user')->get() as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row" style="margin-bottom: 16px;">
            <div class="form-group">
                <label for="scheduled_date">Interview Date <span style="color:var(--danger)">*</span></label>
                <input type="date" id="scheduled_date" name="scheduled_date" value="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
            </div>

            <div class="form-group">
                <label for="scheduled_time">Interview Time <span style="color:var(--danger)">*</span></label>
                <input type="text" id="scheduled_time" name="scheduled_time" value="2:00 PM - 2:30 PM" required>
            </div>

            <div class="form-group">
                <label for="method">Assessment Method <span style="color:var(--danger)">*</span></label>
                <select id="method" name="method" required>
                    <option value="online_call">Online Video Call (Zoom/Google Meet)</option>
                    <option value="phone">Direct Phone Verification</option>
                    <option value="in_person">In-Person at Barangay Logistics Hub</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label for="meeting_link_or_location">Meeting Link or Exact Location</label>
            <input type="text" id="meeting_link_or_location" name="meeting_link_or_location" value="Makati Community Center Admin Office, Room 102" placeholder="e.g. https://meet.google.com/xyz or Office address">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="questions">Assessment Questions &amp; Verification Focus</label>
            <textarea id="questions" name="questions" rows="2" placeholder="e.g. Verify pupil roster size, validate current unemployment status, assess alternate relief aid received."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" size="16" /> Schedule Interview &amp; Dispatch Notification
        </button>
    </form>
</div>

<!-- Scheduled Interviews Table -->
<div class="card">
    <div class="card-header">
        <h3>Interview Records &amp; Outcomes</h3>
    </div>

    @if($interviews->isEmpty())
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <x-icon name="mic" size="36" style="margin-bottom: 8px;" />
            <p>No verification interviews currently recorded.</p>
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>User</th>
                        <th>Associated Request</th>
                        <th>Method</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($interviews as $itw)
                        <tr>
                            <td>
                                <div style="font-weight: 700;">{{ $itw->scheduled_date->format('M d, Y') }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $itw->scheduled_time }}</div>
                            </td>
                            <td>
                                <div>{{ $itw->user->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $itw->user->phone }}</div>
                            </td>
                            <td>
                                @if($itw->request)
                                    #{{ $itw->request->id }} — {{ Str::limit($itw->request->item_name, 24) }}
                                @else
                                    <span style="color: var(--text-muted);">General</span>
                                @endif
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $itw->method)) }}</td>
                            <td>
                                <span class="status-pill status-{{ $itw->result === 'verified' ? 'approved' : ($itw->result === 'not_verified' ? 'rejected' : 'pending') }}">
                                    {{ ucfirst(str_replace('_', ' ', $itw->result)) }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('itw-outcome-{{ $itw->id }}').style.display = document.getElementById('itw-outcome-{{ $itw->id }}').style.display === 'none' ? 'block' : 'none'">
                                    Outcome
                                </button>
                            </td>
                        </tr>
                        <tr id="itw-outcome-{{ $itw->id }}" style="display: none; background: var(--surface-soft);">
                            <td colspan="6" style="padding: 16px;">
                                <form action="{{ route('admin.interviews.result', $itw->id) }}" method="POST">
                                    @csrf
                                    <div class="form-row">
                                        <div class="form-group" style="max-width: 240px;">
                                            <label for="res-{{ $itw->id }}" style="font-size: 12px;">Update Result:</label>
                                            <select id="res-{{ $itw->id }}" name="result" required style="font-size: 13px;">
                                                <option value="verified" {{ $itw->result == 'verified' ? 'selected' : '' }}>Verified (Approved)</option>
                                                <option value="needs_information" {{ $itw->result == 'needs_information' ? 'selected' : '' }}>Needs More Information</option>
                                                <option value="further_review" {{ $itw->result == 'further_review' ? 'selected' : '' }}>Further Review</option>
                                                <option value="not_verified" {{ $itw->result == 'not_verified' ? 'selected' : '' }}>Not Verified (Declined)</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="flex: 1;">
                                            <label for="notes-{{ $itw->id }}" style="font-size: 12px;">Assessment Notes:</label>
                                            <input type="text" id="notes-{{ $itw->id }}" name="notes" value="{{ $itw->notes }}" required placeholder="Summary of interview findings" style="font-size: 13px;">
                                        </div>
                                        <div style="display: flex; align-items: flex-end; margin-bottom: 18px;">
                                            <button type="submit" class="btn btn-primary btn-sm">Save Outcome</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $interviews->links() }}
        </div>
    @endif
</div>
@endsection
