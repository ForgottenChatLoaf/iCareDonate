<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        return view('admin.support.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['user', 'assignee'])->findOrFail($id);
        return view('admin.support.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string|max:3000',
            'status' => 'required|in:in_progress,resolved,closed',
        ]);

        $ticket = SupportTicket::findOrFail($id);

        $ticket->update([
            'admin_reply' => $validated['admin_reply'],
            'status' => $validated['status'],
            'replied_at' => now(),
            'assigned_to' => auth()->id(),
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed']) ? now() : null,
        ]);

        if ($ticket->user_id) {
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'ADMIN_MESSAGE',
                'title' => 'Support Concern Reply',
                'message' => "Our team has replied to your concern '{$ticket->subject}': {$validated['admin_reply']}",
                'related_type' => 'support_ticket',
                'related_id' => $ticket->id,
            ]);
        }

        AuditLog::log('TICKET_REPLIED', 'support_ticket', $ticket->id, "Admin replied to ticket '{$ticket->subject}'. Status: {$validated['status']}.");

        return back()->with('success', 'Reply sent and ticket status updated.');
    }
}
