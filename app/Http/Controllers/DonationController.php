<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Donation;
use App\Models\DonationImage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::with(['category', 'images', 'donor'])
            ->where('status', 'approved');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('pickup_city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('condition')) {
            $query->where('condition_type', $request->condition);
        }

        $donations = $query->latest('approved_at')->paginate(9)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('public.donations.index', compact('donations', 'categories'));
    }

    public function show($id)
    {
        $donation = Donation::with(['category', 'images', 'donor', 'requests'])
            ->findOrFail($id);

        // If donation is not approved, only the donor or an admin may view it
        if ($donation->status !== 'approved') {
            if (!auth()->check() || (auth()->id() !== $donation->donor_id && !auth()->user()->isAdmin())) {
                abort(403, 'This donation is currently under verification and not yet public.');
            }
        }

        return view('public.donations.show', compact('donation'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        return view('user.donations.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'quantity_total' => 'required|integer|min:1|max:10000',
            'unit' => 'required|string|max:50',
            'condition_type' => 'required|in:new,like_new,good,fair',
            'expiration_date' => 'nullable|date|after_or_equal:today',
            'pickup_method' => 'required|in:pickup,delivery,both',
            'pickup_address' => 'required|string|max:500',
            'pickup_barangay' => 'nullable|string|max:150',
            'pickup_city' => 'nullable|string|max:100',
            'pickup_province' => 'nullable|string|max:100',
            'pickup_notes' => 'nullable|string|max:1000',
            'pickup_schedule' => 'nullable|string|max:255',
            'donor_name' => 'required|string|max:150',
            'contact_number' => 'required|string|max:50',
            'description' => 'required|string|max:3000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $donation = Donation::create([
            'donor_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity_total' => $validated['quantity_total'],
            'quantity_reserved' => 0,
            'quantity_claimed' => 0,
            'unit' => $validated['unit'],
            'condition_type' => $validated['condition_type'],
            'expiration_date' => $validated['expiration_date'] ?? null,
            'pickup_method' => $validated['pickup_method'],
            'pickup_address' => $validated['pickup_address'],
            'pickup_barangay' => $validated['pickup_barangay'] ?? null,
            'pickup_city' => $validated['pickup_city'] ?? null,
            'pickup_province' => $validated['pickup_province'] ?? null,
            'pickup_notes' => $validated['pickup_notes'] ?? null,
            'pickup_schedule' => $validated['pickup_schedule'] ?? null,
            'donor_name' => $validated['donor_name'],
            'contact_number' => $validated['contact_number'],
            'status' => 'pending_verification',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('images')) {
            $first = true;
            foreach ($request->file('images') as $file) {
                $path = $file->store('donations', 'public');
                DonationImage::create([
                    'donation_id' => $donation->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'is_primary' => $first,
                ]);
                $first = false;
            }
        }

        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'DONATION_SUBMITTED',
            'title' => 'Donation Submitted for Verification',
            'message' => "Your donation '{$donation->title}' has been submitted. Our administrators will review the items shortly.",
            'related_type' => 'donation',
            'related_id' => $donation->id,
        ]);

        AuditLog::log('DONATION_SUBMITTED', 'donation', $donation->id, "Donation '{$donation->title}' submitted by donor.");

        return redirect()->route('donations.preview', $donation->id)
            ->with('success', 'Your donation offer has been successfully submitted for administrative verification!');
    }

    public function preview($id)
    {
        $donation = Donation::with(['category', 'images', 'donor'])->findOrFail($id);

        if ($donation->donor_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('user.donations.preview', compact('donation'));
    }

    public function edit($id)
    {
        $donation = Donation::with('images')->findOrFail($id);

        if ($donation->donor_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        return view('user.donations.edit', compact('donation', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->donor_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'quantity_total' => 'required|integer|min:1',
            'unit' => 'required|string|max:50',
            'condition_type' => 'required|in:new,like_new,good,fair',
            'expiration_date' => 'nullable|date',
            'pickup_method' => 'required|in:pickup,delivery,both',
            'pickup_address' => 'required|string|max:500',
            'pickup_notes' => 'nullable|string|max:1000',
            'pickup_schedule' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:50',
            'description' => 'required|string|max:3000',
        ]);

        $donation->update([
            'title' => $validated['title'],
            'category_id' => $validated['category_id'],
            'quantity_total' => $validated['quantity_total'],
            'unit' => $validated['unit'],
            'condition_type' => $validated['condition_type'],
            'expiration_date' => $validated['expiration_date'] ?? null,
            'pickup_method' => $validated['pickup_method'],
            'pickup_address' => $validated['pickup_address'],
            'pickup_notes' => $validated['pickup_notes'] ?? null,
            'pickup_schedule' => $validated['pickup_schedule'] ?? null,
            'contact_number' => $validated['contact_number'],
            'description' => $validated['description'],
            'status' => 'pending_verification', // re-trigger verification upon edit
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('donations', 'public');
                DonationImage::create([
                    'donation_id' => $donation->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'is_primary' => false,
                ]);
            }
        }

        AuditLog::log('DONATION_UPDATED', 'donation', $donation->id, "Donation updated and resubmitted for verification.");

        return redirect()->route('user.donations')->with('success', 'Donation updated and resubmitted for verification.');
    }
}
