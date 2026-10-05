<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\Notification;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::with(['category', 'donor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('donor_name', 'like', "%{$s}%")
                  ->orWhere('pickup_city', 'like', "%{$s}%");
            });
        }

        $donations = $query->latest()->paginate(12)->withQueryString();

        return view('admin.donations.index', compact('donations'));
    }

    public function show($id)
    {
        $donation = Donation::with(['category', 'images', 'donor', 'approver', 'requests.requester', 'transactions'])
            ->findOrFail($id);

        return view('admin.donations.show', compact('donation'));
    }

    public function approve(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        $donation->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'admin_notes' => $request->admin_notes ?? $donation->admin_notes,
        ]);

        Notification::create([
            'user_id' => $donation->donor_id,
            'type' => 'DONATION_APPROVED',
            'title' => 'Donation Approved & Available',
            'message' => "Your donation '{$donation->title}' has been verified and is now publicly available for matching.",
            'related_type' => 'donation',
            'related_id' => $donation->id,
        ]);

        AuditLog::log('DONATION_APPROVED', 'donation', $donation->id, "Admin approved donation '{$donation->title}'.");

        return back()->with('success', 'Donation approved successfully! It is now listed as Available.');
    }

    public function requestInfo(Request $request, $id)
    {
        $request->validate(['info_request' => 'required|string|max:1000']);
        $donation = Donation::findOrFail($id);

        $donation->update([
            'status' => 'needs_information',
            'info_request' => $request->info_request,
            'admin_notes' => $request->admin_notes ?? $donation->admin_notes,
        ]);

        Notification::create([
            'user_id' => $donation->donor_id,
            'type' => 'DONATION_NEEDS_INFO',
            'title' => 'Additional Information Requested',
            'message' => "Administrator requested details for '{$donation->title}': {$request->info_request}",
            'related_type' => 'donation',
            'related_id' => $donation->id,
        ]);

        AuditLog::log('DONATION_INFO_REQUESTED', 'donation', $donation->id, "Admin requested info: {$request->info_request}");

        return back()->with('success', 'Information request sent to the donor.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $donation = Donation::findOrFail($id);

        $donation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'admin_notes' => $request->admin_notes ?? $donation->admin_notes,
        ]);

        Notification::create([
            'user_id' => $donation->donor_id,
            'type' => 'DONATION_REJECTED',
            'title' => 'Donation Offer Declined',
            'message' => "Your donation offer for '{$donation->title}' could not be accepted. Reason: {$request->rejection_reason}",
            'related_type' => 'donation',
            'related_id' => $donation->id,
        ]);

        AuditLog::log('DONATION_REJECTED', 'donation', $donation->id, "Donation rejected. Reason: {$request->rejection_reason}");

        return back()->with('success', 'Donation has been rejected with recorded reason.');
    }

    public function destroy($id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->quantity_claimed > 0) {
            return back()->with('error', 'Cannot delete a donation with claimed transactions.');
        }

        $title = $donation->title;
        $donation->delete();

        AuditLog::log('DONATION_DELETED', 'donation', $id, "Admin deleted donation record '{$title}'.");

        return redirect()->route('admin.donations.index')->with('success', 'Donation record deleted.');
    }
}
