<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Donation;
use App\Models\Faq;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->withCount(['donations' => function($q) {
            $q->where('status', 'approved');
        }])->orderBy('sort_order')->take(6)->get();

        $featuredDonations = Donation::with(['category', 'images', 'donor'])
            ->where('status', 'approved')
            ->latest('approved_at')
            ->take(6)
            ->get();

        $stats = [
            'total_donations' => Donation::whereIn('status', ['approved', 'fully_allocated'])->count(),
            'total_items_shared' => Donation::whereIn('status', ['approved', 'fully_allocated'])->sum('quantity_total'),
            'total_transactions' => Transaction::count(),
            'completed_claims' => Transaction::where('status', 'completed')->count(),
            'active_members' => User::where('status', 'active')->count(),
        ];

        return view('public.home', compact('categories', 'featuredDonations', 'stats'));
    }

    public function about()
    {
        $stats = [
            'total_donations' => Donation::whereIn('status', ['approved', 'fully_allocated'])->count(),
            'total_items_shared' => Donation::whereIn('status', ['approved', 'fully_allocated'])->sum('quantity_total'),
            'completed_claims' => Transaction::where('status', 'completed')->count(),
            'active_members' => User::where('status', 'active')->count(),
        ];

        return view('public.about', compact('stats'));
    }

    public function howItWorks()
    {
        return view('public.how-it-works');
    }

    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['donations' => function($q) {
                $q->where('status', 'approved');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('public.categories', compact('categories'));
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        SupportTicket::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return back()->with('success', 'Thank you! Your message and inquiry has been received. Our team will get back to you shortly.');
    }

    public function faqs()
    {
        $faqs = Faq::where('is_published', true)->orderBy('sort_order')->get()->groupBy('category');
        return view('public.faqs', compact('faqs'));
    }
}
