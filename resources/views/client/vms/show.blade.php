@extends('layouts.app')
@section('title', $vm->name)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('client.vms.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Mes VMs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $vm->name }}</h1>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold
        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' :
           ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
        <span class="w-2 h-2 rounded-full {{ $vm->status === 'running' ? 'bg-green-500 animate-pulse' : ($vm->status === 'hibernated' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
        {{ ucfirst($vm->status) }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Contrôles --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-4">Actions</h2>
            <div class="space-y-2">

                @if($vm->status === 'stopped')
                <form method="POST" action="{{ route('client.vms.start', $vm) }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Démarrer
                    </button>
                </form>
                @endif

                @if($vm->status === 'running')
                <form method="POST" action="{{ route('client.vms.stop', $vm) }}" onsubmit="return confirm('Arrêter proprement la VM ?')">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/></svg>
                        Arrêter
                    </button>
                </form>

                <form method="POST" action="{{ route('client.vms.reboot', $vm) }}" onsubmit="return confirm('Redémarrer la VM ?')">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Redémarrer
                    </button>
                </form>

                <form method="POST" action="{{ route('client.vms.hibernate', $vm) }}" onsubmit="return confirm('Mettre en hibernation ?')">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-lg hover:bg-amber-600 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        Hiberner
                    </button>
                </form>
                @endif

                @if($vm->status === 'hibernated')
                <form method="POST" action="{{ route('client.vms.resume', $vm) }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        Reprendre
                    </button>
                </form>
                @endif

                <a href="{{ route('client.vms.terminal', $vm) }}"
                   class="flex items-center gap-2 px-4 py-2.5 bg-gray-800 text-green-400 text-sm font-semibold rounded-lg hover:bg-gray-900 transition font-mono">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Terminal (noVNC)
                </a>
            </div>
        </div>

        {{-- Infos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-3">Informations</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">vCPU</dt><dd class="font-medium">{{ $vm->cores }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">RAM</dt><dd class="font-medium">{{ $vm->memory_mb >= 1024 ? round($vm->memory_mb / 1024, 1) . ' GB' : $vm->memory_mb . ' MB' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Disque</dt><dd class="font-medium">{{ $vm->disk_gb }} GB</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">IP Tailscale</dt><dd class="font-mono text-xs">{{ $vm->tailscale_ip ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Sous-domaine</dt><dd class="font-mono text-xs break-all">{{ $vm->subdomain ?: '—' }}</dd></div>
                @if($vm->custom_domain)
                <div class="flex justify-between"><dt class="text-gray-500">Domaine</dt><dd class="font-mono text-xs break-all">{{ $vm->custom_domain }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-gray-500">Prix</dt><dd class="font-medium">{{ number_format($vm->monthly_price, 2) }}€/mois</dd></div>
            </dl>
        </div>
    </div>

    {{-- Console status --}}
    <div class="lg:col-span-2 space-y-4">
        @if($status)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-4">État temps réel</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-gray-800">{{ number_format(($status['cpu'] ?? 0) * 100, 1) }}%</div>
                    <div class="text-xs text-gray-500">CPU</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-gray-800">
                        {{ isset($status['mem']) ? round($status['mem'] / 1024 / 1024, 0) : 0 }} MB
                    </div>
                    <div class="text-xs text-gray-500">RAM utilisée</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-gray-800">
                        {{ isset($status['netin']) ? round($status['netin'] / 1024 / 1024, 1) : 0 }} MB
                    </div>
                    <div class="text-xs text-gray-500">Réseau entrant</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-gray-800">
                        {{ isset($status['uptime']) ? gmdate('H:i:s', $status['uptime']) : '—' }}
                    </div>
                    <div class="text-xs text-gray-500">Uptime</div>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-3">Domaine personnalisé</h2>
            <p class="text-sm text-gray-500 mb-3">
                Pour utiliser votre propre domaine, pointez un enregistrement <strong>A</strong> vers l'IP publique du panel,
                puis renseignez votre domaine ci-dessous.
            </p>
            <div class="bg-gray-50 rounded-lg p-3 font-mono text-xs text-gray-600 mb-3">
                IP NPM publique : <strong>{{ gethostbyname(parse_url(\App\Models\Setting::get('npm_host', ''), PHP_URL_HOST)) }}</strong>
            </div>
            <form method="POST" action="{{ route('admin.vms.update', $vm) }}" class="flex gap-2">
                @csrf @method('PUT')
                <input type="text" name="custom_domain" value="{{ $vm->custom_domain }}" placeholder="mondomaine.com"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Enregistrer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
