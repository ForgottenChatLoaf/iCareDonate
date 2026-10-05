<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\ResourceMatch;
use App\Models\ResourceRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MatchController extends Controller
{
    public function index()
    {
        // Unfulfilled or partially fulfilled approved requests
        $requests = ResourceRequest::with(['requester', 'donation', 'category'])
            ->whereIn('status', ['approved', 'proof_review', 'submitted'])
            ->whereRaw('quantity_matched < quantity_requested')
            ->latest('urgency_level')
            ->get();

        // Approved donations with available quantities
        $donations = Donation::with(['category', 'donor'])
            ->where('status', 'approved')
            ->get()
            ->filter(fn($d) => $d->quantity_available > 0);

        // Active matches
        $recentMatches = ResourceMatch::with(['donation', 'request.requester', 'transaction'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.matches.index', compact('requests', 'donations', 'recentMatches'));
    }

    public function createMatch(Request $httpRequest)
    {
        $validated = $httpRequest->validate([
            'request_id' => 'required|exists:requests,id',
            'donation_id' => 'required|exists:donations,id',
            'quantity' => 'required|integer|min:1',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|string|max:50',
            'pickup_address' => 'required|string|max:500',
            'claim_deadline_days' => 'required|integer|min:1|max:30',
            'match_notes' => 'nullable|string|max:1000',
        ]);

        $request = ResourceRequest::with('requester')->findOrFail($validated['request_id']);
        $donation = Donation::with('donor')->findOrFail($validated['donation_id']);

        $needed = $request->quantity_requested - $request->quantity_matched;
        if ($validated['quantity'] > $needed) {
            return back()->with('error', "Cannot allocate {$validated['quantity']} items. Request only needs {$needed} more items.");
        }

        if ($validated['quantity'] > $donation->quantity_available) {
            return back()->with('error', "Cannot allocate {$validated['quantity']} items. Donation only has {$donation->quantity_available} available items.");
        }

        // 1. Create ResourceMatch
        $match = ResourceMatch::create([
            'donation_id' => $donation->id,
            'request_id' => $request->id,
            'created_by' => auth()->id(),
            'quantity' => $validated['quantity'],
            'match_notes' => $validated['match_notes'] ?? "Admin allocated {$validated['quantity']} items from {$donation->title}",
            'status' => 'active',
        ]);

        // 2. Reserve Donation Quantity
        $donation->quantity_reserved += $validated['quantity'];
        if ($donation->quantity_available == 0) {
            $donation->status = 'fully_allocated';
        }
        $donation->save();

        // 3. Update Request matched count & status
        $request->quantity_matched += $validated['quantity'];
        if ($request->quantity_matched >= $request->quantity_requested) {
            $request->status = 'matched';
        }
        $request->save();

        // 4. Generate Transaction & Claim
        $claimCode = 'CLAIM-' . strtoupper(Str::random(6));
        $claimDeadline = Carbon::parse($validated['pickup_date'])->addDays((int) $validated['claim_deadline_days']);

        $transaction = Transaction::create([
            'match_id' => $match->id,
            'donation_id' => $donation->id,
            'request_id' => $request->id,
            'donor_id' => $donation->donor_id,
            'requester_id' => $request->requester_id,
            'quantity' => $validated['quantity'],
            'claim_code' => $claimCode,
            'status' => 'pending_claim',
            'pickup_method' => $donation->pickup_method === 'delivery' ? 'delivery' : 'pickup',
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_address' => $validated['pickup_address'],
            'claim_deadline' => $claimDeadline,
            'admin_notes' => $validated['match_notes'] ?? null,
        ]);

        // 5. Notify Requester
        Notification::create([
            'user_id' => $request->requester_id,
            'type' => 'MATCH_CREATED',
            'title' => 'Resource Matched! Claim Ready',
            'message' => "Your request for {$request->item_name} has been matched with {$validated['quantity']} item(s)! Claim Code: {$claimCode}. Pickup date: {$validated['pickup_date']}.",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        // 6. Notify Donor
        Notification::create([
            'user_id' => $donation->donor_id,
            'type' => 'TRANSACTION_CREATED',
            'title' => 'Resource Allocated for Beneficiary',
            'message' => "An administrator has matched {$validated['quantity']} item(s) from '{$donation->title}' for a verified request. Transaction code: {$claimCode}.",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        AuditLog::log('MATCH_CREATED', 'match', $match->id, "Admin matched {$validated['quantity']} items between Donation #{$donation->id} and Request #{$request->id}. Claim: {$claimCode}.");

        return redirect()->route('admin.transactions.index')
            ->with('success', "Match created successfully! Transaction #{$transaction->id} generated with claim code: {$claimCode}.");
    }
}
