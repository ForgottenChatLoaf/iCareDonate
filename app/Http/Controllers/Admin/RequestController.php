<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ResourceRequest::with(['requester', 'donation', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency_level', $request->urgency);
        }

        $requests = $query->latest('submitted_at')->paginate(12)->withQueryString();

        return view('admin.requests.index', compact('requests'));
    }

    public function show($id)
    {
        $request = ResourceRequest::with(['requester', 'donation.category', 'donation.images', 'proofs', 'matches.donation', 'transactions', 'interview'])
            ->findOrFail($id);

        return view('admin.requests.show', compact('request'));
    }

    public function approve(Request $httpRequest, $id)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);

        $resourceRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'admin_notes' => $httpRequest->admin_notes ?? $resourceRequest->admin_notes,
            'interview_required' => $httpRequest->boolean('interview_required'),
            'interview_reason' => $httpRequest->interview_reason ?? null,
        ]);

        Notification::create([
            'user_id' => $resourceRequest->requester_id,
            'type' => 'REQUEST_APPROVED',
            'title' => 'Resource Request Approved',
            'message' => "Your request for {$resourceRequest->quantity_requested} {$resourceRequest->item_name} has been approved and queued for allocation.",
            'related_type' => 'request',
            'related_id' => $resourceRequest->id,
        ]);

        AuditLog::log('REQUEST_APPROVED', 'request', $resourceRequest->id, "Admin approved request for '{$resourceRequest->item_name}'.");

        return back()->with('success', 'Request has been approved and moved to the matching pool.');
    }

    public function reject(Request $httpRequest, $id)
    {
        $httpRequest->validate(['rejection_reason' => 'required|string|max:1000']);
        $resourceRequest = ResourceRequest::findOrFail($id);

        $resourceRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $httpRequest->rejection_reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'admin_notes' => $httpRequest->admin_notes ?? $resourceRequest->admin_notes,
        ]);

        Notification::create([
            'user_id' => $resourceRequest->requester_id,
            'type' => 'REQUEST_REJECTED',
            'title' => 'Request Update',
            'message' => "Your request for {$resourceRequest->item_name} could not be approved. Reason: {$httpRequest->rejection_reason}",
            'related_type' => 'request',
            'related_id' => $resourceRequest->id,
        ]);

        AuditLog::log('REQUEST_REJECTED', 'request', $resourceRequest->id, "Request rejected. Reason: {$httpRequest->rejection_reason}");

        return back()->with('success', 'Request has been rejected.');
    }

    public function destroy($id)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);
        $name = $resourceRequest->item_name;
        $resourceRequest->delete();

        AuditLog::log('REQUEST_DELETED', 'request', $id, "Admin deleted request '{$name}'.");

        return redirect()->route('admin.requests.index')->with('success', 'Request record deleted.');
    }
}
