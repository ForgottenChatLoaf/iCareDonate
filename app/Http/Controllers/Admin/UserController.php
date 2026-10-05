<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['donations', 'requests']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::with(['donations.category', 'requests.donation', 'transactionsAsDonor', 'transactionsAsRequester'])
            ->withCount(['donations', 'requests'])
            ->findOrFail($id);

        $auditLogs = AuditLog::where('target_type', 'user')
            ->where('target_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return view('admin.users.show', compact('user', 'auditLogs'));
    }

    public function moderate(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:warn,suspend,ban,restore',
            'reason' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot apply moderation actions to your own administrator account.');
        }

        switch ($validated['action']) {
            case 'warn':
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'ACCOUNT_WARNING',
                    'title' => 'Official Community Warning',
                    'message' => "An administrator issued an official warning regarding your account activity: {$validated['reason']}",
                    'related_type' => 'user',
                    'related_id' => $user->id,
                ]);
                $msg = "Official warning sent to {$user->name}.";
                break;

            case 'suspend':
                $user->update([
                    'status' => 'suspended',
                    'suspension_reason' => $validated['reason'],
                    'suspended_at' => now(),
                ]);
                $msg = "Account for {$user->name} temporarily suspended.";
                break;

            case 'ban':
                $user->update([
                    'status' => 'banned',
                    'suspension_reason' => $validated['reason'],
                    'suspended_at' => now(),
                ]);
                $msg = "Account for {$user->name} permanently banned.";
                break;

            case 'restore':
                $user->update([
                    'status' => 'active',
                    'suspension_reason' => null,
                    'suspended_at' => null,
                ]);
                $msg = "Account for {$user->name} restored to active status.";
                break;
        }

        AuditLog::log("USER_MODERATION_" . strtoupper($validated['action']), 'user', $user->id, "Action: {$validated['action']}. Target: {$user->email}.", $validated['reason']);

        return back()->with('success', $msg);
    }
}
