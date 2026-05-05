<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VirtualMachine;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'clients'          => User::where('is_admin', false)->count(),
            'vms'              => VirtualMachine::count(),
            'vms_running'      => VirtualMachine::where('status', 'running')->count(),
            'open_tickets'     => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'revenue_month'    => Invoice::where('status', 'paid')
                                    ->whereMonth('paid_at', now()->month)
                                    ->whereYear('paid_at', now()->year)
                                    ->sum('total'),
            'revenue_total'    => Invoice::where('status', 'paid')->sum('total'),
        ];

        $recentClients = User::where('is_admin', false)
            ->latest()
            ->limit(5)
            ->get();

        $recentTickets = Ticket::with('user')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentClients', 'recentTickets', 'recentInvoices'));
    }
}
