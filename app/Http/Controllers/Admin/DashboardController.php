<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\ResourceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $kpi = [
            'pending_donations' => Donation::where('status', 'pending_verification')->count(),
            'pending_requests' => ResourceRequest::whereIn('status', ['submitted', 'proof_review'])->count(),
            'active_claims' => Transaction::where('status', 'pending_claim')->count(),
            'completed_transactions' => Transaction::where('status', 'completed')->count(),
            'total_users' => User::where('role', 'user')->count(),
            'total_donations' => Donation::count(),
            'approved_donations' => Donation::where('status', 'approved')->count(),
        ];

        $pendingDonations = Donation::with(['category', 'donor'])
            ->where('status', 'pending_verification')
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $pendingRequests = ResourceRequest::with(['category', 'requester', 'donation'])
            ->whereIn('status', ['submitted', 'proof_review'])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $recentTransactions = Transaction::with(['donation', 'donor', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        $recentAuditLogs = AuditLog::with('actor')->latest()->take(6)->get();

        return view('admin.dashboard', compact('kpi', 'pendingDonations', 'pendingRequests', 'recentTransactions', 'recentAuditLogs'));
    }
}
