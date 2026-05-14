<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Ticket;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $vms = $user->virtualMachines()->latest()->get();
        $hostingAccounts = $user->hostingAccounts()->where('is_active', true)->get();

        $openTickets    = $user->tickets()->whereIn('status', ['open', 'in_progress'])->count();
        $unpaidInvoices = $user->invoices()->where('status', 'pending')->count();
        $pendingQuotes  = $user->quotes()->whereIn('status', ['sent', 'viewed'])->where('is_template', false)->latest()->limit(5)->get();
        $recentInvoices = $user->invoices()->latest()->limit(5)->get();
        $openTicketsList = $user->tickets()->whereIn('status', ['open', 'in_progress'])->latest()->limit(5)->get();

        return view('client.dashboard', compact('vms', 'hostingAccounts', 'openTickets', 'unpaidInvoices', 'pendingQuotes', 'recentInvoices', 'openTicketsList'));
    }
}
