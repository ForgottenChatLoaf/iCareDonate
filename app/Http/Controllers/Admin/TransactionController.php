<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['donation.category', 'request', 'donor', 'requester']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('claim_code', 'like', "%{$s}%")
                  ->orWhereHas('donor', fn($u) => $u->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('requester', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = Transaction::with(['donation.category', 'donation.images', 'request', 'donor', 'requester', 'canceller'])
            ->findOrFail($id);

        return view('admin.transactions.show', compact('transaction'));
    }

    public function complete($id)
    {
        $transaction = Transaction::with(['donation', 'request'])->findOrFail($id);

        if (!in_array($transaction->status, ['pending_claim', 'claimed'])) {
            return back()->with('error', 'Cannot complete this transaction in its current state.');
        }

        $transaction->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $donation = $transaction->donation;
        $donation->quantity_reserved = max(0, $donation->quantity_reserved - $transaction->quantity);
        $donation->quantity_claimed += $transaction->quantity;
        if ($donation->quantity_available == 0) {
            $donation->status = 'fully_allocated';
        }
        $donation->save();

        if ($transaction->request) {
            $transaction->request->update(['status' => 'completed']);
        }

        Notification::create([
            'user_id' => $transaction->requester_id,
            'type' => 'TRANSACTION_COMPLETED',
            'title' => 'Resource Handoff Completed',
            'message' => "Your transaction with Claim Code {$transaction->claim_code} has been marked completed by the admin.",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        AuditLog::log('TRANSACTION_COMPLETED', 'transaction', $transaction->id, "Admin marked transaction #{$transaction->id} as completed.");

        return back()->with('success', 'Transaction marked as completed.');
    }

    public function cancel(Request $request, $id)
    {
        $request->validate(['cancel_reason' => 'required|string|max:1000']);
        $transaction = Transaction::with(['donation', 'request'])->findOrFail($id);

        if ($transaction->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed transaction.');
        }

        $transaction->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $request->cancel_reason,
            'cancelled_by' => auth()->id(),
        ]);

        // Revert reserved quantities back to available
        $donation = $transaction->donation;
        $donation->quantity_reserved = max(0, $donation->quantity_reserved - $transaction->quantity);
        if ($donation->status === 'fully_allocated' && $donation->quantity_available > 0) {
            $donation->status = 'approved';
        }
        $donation->save();

        if ($transaction->request) {
            $req = $transaction->request;
            $req->quantity_matched = max(0, $req->quantity_matched - $transaction->quantity);
            if ($req->status === 'matched') {
                $req->status = 'approved';
            }
            $req->save();
        }

        Notification::create([
            'user_id' => $transaction->requester_id,
            'type' => 'TRANSACTION_CANCELLED',
            'title' => 'Claim Cancelled',
            'message' => "Your claim {$transaction->claim_code} was cancelled. Reason: {$request->cancel_reason}",
            'related_type' => 'transaction',
            'related_id' => $transaction->id,
        ]);

        AuditLog::log('TRANSACTION_CANCELLED', 'transaction', $transaction->id, "Admin cancelled transaction. Reason: {$request->cancel_reason}. Quantities reverted.");

        return back()->with('success', 'Transaction cancelled and reserved item quantities have been safely restored.');
    }
}
