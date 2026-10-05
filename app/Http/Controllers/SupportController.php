<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index()
    {
        $tickets = auth()->user()->supportTickets()->latest()->paginate(10);
        return view('user.support.index', compact('tickets'));
    }

    public function create()
    {
        return view('user.support.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'message' => 'required|string|max:3000',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        AuditLog::log('TICKET_SUBMITTED', 'support_ticket', $ticket->id, "User submitted support concern '{$ticket->subject}'.");

        return redirect()->route('support.show', $ticket->id)->with('success', 'Your support ticket has been submitted. Our team will review and reply.');
    }

    public function show($id)
    {
        $ticket = auth()->user()->supportTickets()->findOrFail($id);
        return view('user.support.show', compact('ticket'));
    }
}
