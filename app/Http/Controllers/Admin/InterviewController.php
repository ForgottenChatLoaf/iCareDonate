<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Interview;
use App\Models\Notification;
use App\Models\ResourceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function index()
    {
        $interviews = Interview::with(['user', 'request', 'conductor'])
            ->latest('scheduled_date')
            ->paginate(15);

        $eligibleRequests = ResourceRequest::with('requester')
            ->where('interview_required', true)
            ->whereDoesntHave('interview')
            ->get();

        return view('admin.interviews.index', compact('interviews', 'eligibleRequests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'nullable|exists:requests,id',
            'user_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|string|max:50',
            'method' => 'required|in:online_call,in_person,phone',
            'meeting_link_or_location' => 'nullable|string|max:255',
            'questions' => 'nullable|string|max:2000',
        ]);

        $interview = Interview::create([
            'request_id' => $validated['request_id'] ?? null,
            'user_id' => $validated['user_id'],
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'method' => $validated['method'],
            'meeting_link_or_location' => $validated['meeting_link_or_location'] ?? 'Barangay Center / Video Call link will be sent',
            'questions' => $validated['questions'] ?? null,
            'result' => 'pending',
            'conducted_by' => auth()->id(),
        ]);

        Notification::create([
            'user_id' => $validated['user_id'],
            'type' => 'ADMIN_MESSAGE',
            'title' => 'Verification Interview Scheduled',
            'message' => "An assessment interview has been set for {$validated['scheduled_date']} at {$validated['scheduled_time']} via {$validated['method']}.",
            'related_type' => 'interview',
            'related_id' => $interview->id,
        ]);

        AuditLog::log('INTERVIEW_SCHEDULED', 'interview', $interview->id, "Admin scheduled verification interview with User #{$validated['user_id']}.");

        return back()->with('success', 'Verification interview scheduled successfully.');
    }

    public function updateResult(Request $request, $id)
    {
        $validated = $request->validate([
            'result' => 'required|in:verified,needs_information,further_review,not_verified',
            'notes' => 'required|string|max:2000',
        ]);

        $interview = Interview::findOrFail($id);
        $interview->update([
            'result' => $validated['result'],
            'notes' => $validated['notes'],
            'conducted_by' => auth()->id(),
        ]);

        AuditLog::log('INTERVIEW_RESULT_UPDATED', 'interview', $id, "Interview outcome updated to {$validated['result']}.");

        return back()->with('success', "Interview outcome recorded as: " . ucfirst(str_replace('_', ' ', $validated['result'])));
    }
}
