<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\MailService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = auth()->user()->tickets()->latest()->paginate(15);
        return view('client.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('client.tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:150',
            'category' => 'nullable|string|max:50',
            'priority' => 'required|in:low,normal,high,urgent',
            'message'  => 'required|string|min:10',
        ]);

        $ticket = Ticket::create([
            'user_id'  => auth()->id(),
            'number'   => Ticket::generateNumber(),
            'subject'  => $request->subject,
            'category' => $request->category,
            'priority' => $request->priority,
            'status'   => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'message'   => $request->message,
            'is_admin'  => false,
        ]);

        try {
            app(MailService::class)->sendFromTemplate('ticket_opened', auth()->user()->email, [
                'first_name'     => auth()->user()->first_name,
                'ticket_number'  => $ticket->number,
                'ticket_subject' => $ticket->subject,
                'ticket_url'     => route('client.tickets.show', $ticket),
            ]);
        } catch (\Exception) {}

        return redirect()->route('client.tickets.show', $ticket)
            ->with('success', "Ticket #{$ticket->number} créé. Notre équipe vous répondra rapidement.");
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load('messages.user');
        return view('client.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate(['message' => 'required|string|min:2']);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'message'   => $request->message,
            'is_admin'  => false,
        ]);

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Réponse envoyée.');
    }
}
