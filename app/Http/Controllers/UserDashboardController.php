<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Notification;
use App\Models\ResourceRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'my_donations' => $user->donations()->count(),
            'active_requests' => $user->requests()->whereNotIn('status', ['completed', 'cancelled', 'rejected'])->count(),
            'completed_requests' => $user->requests()->where('status', 'completed')->count(),
            'pending_claims' => Transaction::where('requester_id', $user->id)->where('status', 'pending_claim')->count(),
            'unread_notifications' => $user->notifications()->where('is_read', false)->count(),
        ];

        $recentDonations = $user->donations()->with('category')->latest()->take(3)->get();
        $recentRequests = $user->requests()->with(['donation', 'category'])->latest()->take(3)->get();
        $recentTransactions = Transaction::where(function($q) use ($user) {
            $q->where('donor_id', $user->id)
              ->orWhere('requester_id', $user->id);
        })->with(['donation', 'donor', 'requester'])->latest()->take(4)->get();

        $notifications = $user->notifications()->latest()->take(5)->get();

        return view('user.dashboard', compact('stats', 'recentDonations', 'recentRequests', 'recentTransactions', 'notifications'));
    }

    public function myDonations()
    {
        $donations = auth()->user()->donations()
            ->with(['category', 'images', 'transactions'])
            ->latest()
            ->paginate(10);

        return view('user.donations.index', compact('donations'));
    }

    public function myRequests()
    {
        $requests = auth()->user()->requests()
            ->with(['donation.category', 'transactions'])
            ->latest()
            ->paginate(10);

        return view('user.requests.index', compact('requests'));
    }
}
