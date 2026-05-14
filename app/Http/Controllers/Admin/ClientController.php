<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CyberPanelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('is_admin', false)
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%")
                  ->orWhere('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%");
            }))
            ->withCount(['virtualMachines', 'hostingAccounts', 'tickets'])
            ->latest()
            ->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function show(User $client)
    {
        $client->load([
            'virtualMachines',
            'hostingAccounts',
            'quotes'        => fn($q) => $q->where('is_template', false)->latest()->limit(10),
            'invoices'      => fn($q) => $q->latest()->limit(10),
            'creditNotes'   => fn($q) => $q->latest()->limit(10),
            'tickets'       => fn($q) => $q->latest()->limit(10),
        ]);
        return view('admin.clients.show', compact('client'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:100',
        ]);

        $client = User::create([
            'name'       => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'company'    => $request->company,
            'is_admin'   => false,
            'is_active'  => true,
        ]);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client créé avec succès.');
    }

    public function edit(User $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email,' . $client->id,
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:100',
            'is_active'  => 'boolean',
        ]);

        $client->update([
            'name'                => $request->first_name . ' ' . $request->last_name,
            'first_name'          => $request->first_name,
            'last_name'           => $request->last_name,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'company'             => $request->company,
            'cyberpanel_username' => $request->cyberpanel_username,
            'is_active'           => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(User $client)
    {
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Client supprimé.');
    }

    public function resetPassword(Request $request, User $client)
    {
        $request->validate(['password' => 'required|string|min:8']);

        // Store plain password in request so the Observer can sync to CyberPanel
        $client->update(['password' => Hash::make($request->password)]);

        // Manually sync since observer uses request()->input('password')
        if ($client->cyberpanel_username) {
            try {
                app(CyberPanelService::class)->changeUserPassword($client->cyberpanel_username, $request->password);
            } catch (\Exception $e) {
                return back()->with('error', 'Mot de passe mis à jour localement, mais la sync CyberPanel a échoué : ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Mot de passe réinitialisé et synchronisé avec CyberPanel.');
    }
}
