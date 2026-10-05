<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\RequestProof;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function create(Request $request)
    {
        $donation = null;
        if ($request->filled('donation_id')) {
            $donation = Donation::with('category')->findOrFail($request->donation_id);
            if ($donation->status !== 'approved' || $donation->quantity_available <= 0) {
                return redirect()->route('donations.index')->with('error', 'This donation is currently not available for requests.');
            }
        }

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('user.requests.create', compact('donation', 'categories'));
    }

    public function store(Request $request)
    {
        $rules = [
            'donation_id' => 'nullable|exists:donations,id',
            'category_id' => 'nullable|exists:categories,id',
            'item_name' => 'required|string|max:200',
            'quantity_requested' => 'required|integer|min:1',
            'urgency_level' => 'required|in:low,medium,high,emergency',
            'purpose' => 'required|string|max:3000',
            'contact_number' => 'required|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'proofs.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
        ];

        $donation = null;
        if ($request->filled('donation_id')) {
            $donation = Donation::findOrFail($request->donation_id);
            // Business rule: requested quantity cannot exceed available quantity
            $rules['quantity_requested'] = 'required|integer|min:1|max:' . $donation->quantity_available;
        }

        $validated = $request->validate($rules);

        $resourceRequest = ResourceRequest::create([
            'requester_id' => auth()->id(),
            'donation_id' => $validated['donation_id'] ?? null,
            'category_id' => $validated['category_id'] ?? ($donation ? $donation->category_id : null),
            'item_name' => $validated['item_name'],
            'quantity_requested' => $validated['quantity_requested'],
            'quantity_matched' => 0,
            'purpose' => $validated['purpose'],
            'urgency_level' => $validated['urgency_level'],
            'contact_number' => $validated['contact_number'],
            'delivery_address' => $validated['delivery_address'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $file) {
                $path = $file->store('proofs', 'public');
                RequestProof::create([
                    'request_id' => $resourceRequest->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'description' => 'Supporting proof submitted during application',
                ]);
            }
        }

        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'REQUEST_RECEIVED',
            'title' => 'Resource Request Received',
            'message' => "Your request for {$resourceRequest->quantity_requested} {$resourceRequest->item_name} has been received for verification.",
            'related_type' => 'request',
            'related_id' => $resourceRequest->id,
        ]);

        AuditLog::log('REQUEST_SUBMITTED', 'request', $resourceRequest->id, "Request for '{$resourceRequest->item_name}' submitted by user.");

        return redirect()->route('requests.show', $resourceRequest->id)
            ->with('success', 'Your resource request and supporting proof have been submitted for admin verification.');
    }

    public function show($id)
    {
        $resourceRequest = ResourceRequest::with(['requester', 'donation.images', 'category', 'proofs', 'transactions.donor', 'interview'])
            ->findOrFail($id);

        if ($resourceRequest->requester_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('user.requests.show', compact('resourceRequest'));
    }

    public function cancel($id)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);

        if ($resourceRequest->requester_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        if (in_array($resourceRequest->status, ['completed', 'claimed'])) {
            return back()->with('error', 'Cannot cancel a completed or claimed request.');
        }

        $resourceRequest->update(['status' => 'cancelled']);

        AuditLog::log('REQUEST_CANCELLED', 'request', $resourceRequest->id, 'Requester cancelled request.');

        return back()->with('success', 'Request has been cancelled.');
    }
}
