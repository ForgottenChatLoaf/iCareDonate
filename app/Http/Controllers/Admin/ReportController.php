<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Donation;
use App\Models\ResourceRequest;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_donations' => Donation::count(),
            'approved_donations' => Donation::where('status', 'approved')->count(),
            'total_items_offered' => Donation::sum('quantity_total'),
            'total_items_claimed' => Donation::sum('quantity_claimed'),
            'total_requests' => ResourceRequest::count(),
            'approved_requests' => ResourceRequest::where('status', 'approved')->count(),
            'completed_requests' => ResourceRequest::where('status', 'completed')->count(),
            'total_transactions' => Transaction::count(),
            'completed_transactions' => Transaction::where('status', 'completed')->count(),
            'open_tickets' => SupportTicket::where('status', 'open')->count(),
        ];

        $categories = Category::withCount(['donations', 'requests'])->get();

        $recentTransactions = Transaction::with(['donation', 'donor', 'requester'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.reports.index', compact('stats', 'categories', 'recentTransactions'));
    }

    public function print(Request $request)
    {
        $type = $request->get('type', 'full_impact');

        $donations = Donation::with(['category', 'donor'])->latest()->get();
        $requests = ResourceRequest::with(['requester', 'donation'])->latest()->get();
        $transactions = Transaction::with(['donation', 'donor', 'requester'])->latest()->get();

        $stats = [
            'total_donations' => $donations->count(),
            'total_requests' => $requests->count(),
            'total_transactions' => $transactions->count(),
            'completed_transactions' => $transactions->where('status', 'completed')->count(),
        ];

        return view('admin.reports.print', compact('type', 'donations', 'requests', 'transactions', 'stats'));
    }
}
