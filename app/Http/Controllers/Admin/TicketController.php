<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\MailService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::with('user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate(20);

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('messages.user', 'user', 'assignedTo');
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required|string|min:2']);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'message'   => $request->message,
            'is_admin'  => true,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        try {
            app(MailService::class)->sendFromTemplate('ticket_reply', $ticket->user->email, [
                'first_name'     => $ticket->user->first_name,
                'ticket_number'  => $ticket->number,
                'ticket_subject' => $ticket->subject,
                'reply_message'  => $request->message,
                'ticket_url'     => route('client.tickets.show', $ticket),
            ]);
        } catch (\Exception) {}

        return back()->with('success', 'Réponse envoyée.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $ticket->update([
            'status'    => $request->status,
            'closed_at' => in_array($request->status, ['resolved', 'closed']) ? now() : null,
            'assigned_to' => $request->assigned_to ?? $ticket->assigned_to,
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }
}
