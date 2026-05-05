@extends('layouts.app')
@section('title', $client->full_name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Clients</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $client->full_name }}</h1>
        <p class="text-gray-500 text-sm">{{ $client->email }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.clients.edit', $client) }}"
           class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
            Modifier
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Infos client --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-4">Informations</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Téléphone</dt><dd class="text-gray-800">{{ $client->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Société</dt><dd class="text-gray-800">{{ $client->company ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Pays</dt><dd class="text-gray-800">{{ $client->country }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Statut</dt>
                    <dd><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $client->is_active ? 'Actif' : 'Désactivé' }}</span></dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">CyberPanel</dt><dd class="font-mono text-xs text-gray-800">{{ $client->cyberpanel_username ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Inscription</dt><dd class="text-gray-800">{{ $client->created_at->format('d/m/Y') }}</dd></div>
            </dl>
        </div>

        {{-- Reset password --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="{ open: false }">
            <button @click="open = !open" class="text-sm font-semibold text-red-600 hover:text-red-700">
                Réinitialiser le mot de passe
            </button>
            <div x-show="open" x-transition class="mt-3">
                <form method="POST" action="{{ route('admin.clients.reset-password', $client) }}">
                    @csrf
                    <input type="password" name="password" placeholder="Nouveau mot de passe (min. 8 car.)"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 focus:outline-none mb-2">
                    <button type="submit"
                        class="w-full py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition">
                        Confirmer la réinitialisation
                    </button>
                    <p class="text-xs text-gray-400 mt-1">Le mot de passe sera synchronisé avec CyberPanel.</p>
                </form>
            </div>
        </div>
    </div>

    {{-- VMs + Tickets --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- VMs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Machines virtuelles ({{ $client->virtualMachines->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->virtualMachines as $vm)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-sm text-gray-800">{{ $vm->name }}</div>
                        <div class="text-xs text-gray-400">VMID {{ $vm->proxmox_vmid }} — {{ $vm->cores }} vCPU / {{ $vm->memory_mb }}MB RAM / {{ $vm->disk_gb }}GB</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' : ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $vm->status }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucune VM</div>
                @endforelse
            </div>
        </div>

        {{-- Tickets récents --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Tickets récents</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->tickets as $ticket)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-medium text-sm text-indigo-600 hover:underline">#{{ $ticket->number }} — {{ $ticket->subject }}</a>
                        <div class="text-xs text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $ticket->status }}</span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun ticket</div>
                @endforelse
            </div>
        </div>

        {{-- Factures --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Dernières factures</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->invoices as $invoice)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-sm text-gray-800">{{ $invoice->number }}</div>
                        <div class="text-xs text-gray-400">{{ $invoice->created_at->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-sm {{ $invoice->isPaid() ? 'text-green-600' : 'text-amber-600' }}">{{ number_format($invoice->total, 2) }}€</div>
                        <div class="text-xs text-gray-400">{{ $invoice->isPaid() ? 'Payée' : 'En attente' }}</div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucune facture</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
