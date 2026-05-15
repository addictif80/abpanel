<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteLog;
use App\Models\QuoteMessage;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\VirtualMachine;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SandboxController extends Controller
{
    private array $demoEmails = [
        'alice.martin@demo.test',
        'bruno.dupont@demo.test',
        'camille@demo.test',
    ];

    public function index()
    {
        $stats = $this->getStats();
        return view('admin.sandbox.index', compact('stats'));
    }

    public function seed()
    {
        try {
            (new DemoSeeder())->run();
            return back()->with('success', 'Données de démonstration créées avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function reset()
    {
        $demoUserIds = User::whereIn('email', $this->demoEmails)->pluck('id');

        if ($demoUserIds->isEmpty()) {
            return back()->with('info', 'Aucune donnée de démonstration à supprimer.');
        }

        // Delete related data first (FK order)
        Notification::whereIn('user_id', $demoUserIds)->delete();

        $projectIds = Project::whereIn('user_id', $demoUserIds)->pluck('id');
        ProjectMessage::whereIn('project_id', $projectIds)->delete();
        Project::whereIn('id', $projectIds)->delete();

        $ticketIds = Ticket::whereIn('user_id', $demoUserIds)->pluck('id');
        TicketMessage::whereIn('ticket_id', $ticketIds)->delete();
        Ticket::whereIn('id', $ticketIds)->delete();

        $quoteIds = Quote::whereIn('user_id', $demoUserIds)->pluck('id');
        QuoteMessage::whereIn('quote_id', $quoteIds)->delete();
        QuoteLog::whereIn('quote_id', $quoteIds)->delete();
        QuoteItem::whereIn('quote_id', $quoteIds)->delete();
        Quote::whereIn('id', $quoteIds)->delete();

        Invoice::whereIn('user_id', $demoUserIds)->delete();
        VirtualMachine::whereIn('user_id', $demoUserIds)->delete();
        HostingAccount::whereIn('user_id', $demoUserIds)->delete();

        User::whereIn('id', $demoUserIds)->delete();

        return back()->with('success', 'Données de démonstration supprimées.');
    }

    public function runCommand(Request $request)
    {
        $request->validate(['command' => 'required|in:reminders:send,queue:work']);

        $allowedArgs = [];
        $output = '';

        Artisan::call($request->command, ['--dry-run' => true]);
        $output = Artisan::output();

        return back()->with('command_output', $output)->with('command_run', $request->command);
    }

    private function getStats(): array
    {
        $demoUserIds = User::whereIn('email', $this->demoEmails)->pluck('id');
        $hasDemoData = $demoUserIds->isNotEmpty();

        return [
            'has_demo_data'    => $hasDemoData,
            'demo_clients'     => $demoUserIds->count(),
            'demo_vms'         => $hasDemoData ? VirtualMachine::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_hosting'     => $hasDemoData ? HostingAccount::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_quotes'      => $hasDemoData ? Quote::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_invoices'    => $hasDemoData ? Invoice::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_projects'    => $hasDemoData ? Project::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_tickets'     => $hasDemoData ? Ticket::whereIn('user_id', $demoUserIds)->count() : 0,
            'demo_emails'      => $this->demoEmails,
            'demo_password'    => 'Demo1234!',
        ];
    }
}
