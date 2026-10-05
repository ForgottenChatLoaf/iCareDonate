<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\ResourceRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function show($id)
    {
        $transaction = Transaction::with(['donation.category', 'donation.images', 'request', 'donor', 'requester'])
            ->findOrFail($id);

        $user = auth()->user();
        if ($transaction->donor_id !== $user->id && $transaction->requester_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        return view('user.claims.show', compact('transaction'));
    }

    public function confirm(Request $request, $id)
    {
        $transaction = Transaction::with(['donation', 'request'])->findOrFail($id);
        $user = auth()->user();

        if ($transaction->requester_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if ($transaction->status !== 'pending_claim') {
            return back()->with('error', 'This claim cannot be confirmed at its current stage.');
        }

        $transaction->update([
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        Notification::create([
            'user_id' => $transaction->donor_id,
            'type' => 'CLAIM_READY',
            'title' => 'Claim Confirmed by Requester',
            'message' => "The requester {$transaction->requester->name} has confirmed claim of {$transaction->quantity} item(s). Claim code: {$transaction->claim_code}",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        AuditLog::log('CLAIM_CONFIRMED', 'transaction', $transaction->id, "Claim code {$transaction->claim_code} confirmed by requester.");

        return back()->with('success', 'Claim acknowledged! Please proceed to the pickup location as scheduled with your claim code.');
    }

    public function complete(Request $request, $id)
    {
        $transaction = Transaction::with(['donation', 'request'])->findOrFail($id);
        $user = auth()->user();

        // Donor or Admin can mark as completed/received
        if ($transaction->donor_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if (!in_array($transaction->status, ['pending_claim', 'claimed'])) {
            return back()->with('error', 'This transaction is already completed or cancelled.');
        }

        $transaction->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update donation quantities: reserved quantity shifts to claimed quantity
        $donation = $transaction->donation;
        $donation->quantity_reserved = max(0, $donation->quantity_reserved - $transaction->quantity);
        $donation->quantity_claimed += $transaction->quantity;
        
        if ($donation->quantity_available == 0) {
            $donation->status = 'fully_allocated';
        }
        $donation->save();

        // Update request status to completed
        if ($transaction->request) {
            $transaction->request->update(['status' => 'completed']);
        }

        Notification::create([
            'user_id' => $transaction->requester_id,
            'type' => 'TRANSACTION_COMPLETED',
            'title' => 'Transaction Completed!',
            'message' => "The resource handoff for {$transaction->quantity} {$donation->title} has been successfully completed. Thank you for using iCareDonate!",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        Notification::create([
            'user_id' => $transaction->donor_id,
            'type' => 'TRANSACTION_COMPLETED',
            'title' => 'Resource Successfully Delivered!',
            'message' => "Your donation handoff of {$transaction->quantity} items has been confirmed completed. Thank you for your generosity!",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        AuditLog::log('TRANSACTION_COMPLETED', 'transaction', $transaction->id, "Transaction #{$transaction->id} marked completed by donor/admin.");

        return back()->with('success', 'Transaction successfully completed and marked received!');
    }
}
