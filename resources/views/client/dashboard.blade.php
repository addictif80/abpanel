@extends('layouts.app')
@section('title', 'Mon espace')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bonjour, {{ auth()->user()->first_name ?: auth()->user()->name }} 👋</h1>
    <p class="text-gray-500 text-sm mt-1">Bienvenue sur votre espace client</p>
</div>

{{-- Alertes --}}
@if($unpaidInvoices > 0)
<div class="mb-4 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>Vous avez <strong>{{ $unpaidInvoices }} facture(s) en attente</strong> de paiement.</span>
    <a href="{{ route('client.billing.index') }}" class="ml-auto font-semibold hover:underline">Voir →</a>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-indigo-600">{{ $vms->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">Machine(s) virtuelle(s)</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-green-600">{{ $vms->where('status', 'running')->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">VM(s) en ligne</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-blue-600">{{ $hostingAccounts->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">Hébergement(s)</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-amber-600">{{ $openTickets }}</div>
        <div class="text-xs text-gray-500 mt-1">Ticket(s) ouvert(s)</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- VMs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Mes machines virtuelles</h2>
            <a href="{{ route('client.vms.index') }}" class="text-xs text-indigo-600 hover:underline">Gérer →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($vms->take(4) as $vm)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-sm text-gray-800">{{ $vm->name }}</div>
                    <div class="text-xs text-gray-400">{{ $vm->cores }} vCPU · {{ $vm->memory_mb }}MB · {{ $vm->disk_gb }}GB</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' :
                           ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $vm->status === 'running' ? 'bg-green-500' : ($vm->status === 'hibernated' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                        {{ ucfirst($vm->status) }}
                    </span>
                    <a href="{{ route('client.vms.show', $vm) }}" class="text-xs text-indigo-600 hover:underline">Gérer</a>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                <p>Aucune machine virtuelle</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Hébergements web --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Mes hébergements web</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($hostingAccounts as $hosting)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-sm text-gray-800">{{ $hosting->domain }}</div>
                    <div class="text-xs text-gray-400">{{ $hosting->plan }} · {{ $hosting->disk_mb }}MB</div>
                </div>
                @if(auth()->user()->cyberpanel_username)
                <a href="{{ \App\Models\Setting::get('cyberpanel_host') }}" target="_blank"
                   class="text-xs text-indigo-600 hover:underline">
                    Accéder à CyberPanel →
                </a>
                @endif
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                <p>Aucun hébergement</p>
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
