@extends('layouts.app')
@section('title', 'Mes domaines')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Mes domaines</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $domains->count() }} domaine(s) configuré(s)</p>
    </div>
    <a href="{{ route('client.domains.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter un domaine
    </a>
</div>

@if($npmPublicIp)
<div class="mb-5 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800 flex items-center gap-3">
    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>Vos domaines doivent pointer vers <strong class="font-mono">{{ $npmPublicIp }}</strong> (enregistrement DNS de type <strong>A</strong>).</span>
</div>
@endif

@if($domains->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
    </svg>
    <p class="text-gray-700 font-medium mb-1">Aucun domaine configuré.</p>
    <p class="text-gray-400 text-sm mb-4">Ajoutez un domaine ou sous-domaine pour le rediriger vers votre VPS ou hébergement.</p>
    <a href="{{ route('client.domains.create') }}" class="inline-block px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Ajouter un domaine
    </a>
</div>
@else
<div class="space-y-3">
    @foreach($domains as $domain)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="{ confirmDelete: false }">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono font-semibold text-gray-900">{{ $domain->domain }}</span>
                    @if($domain->www_redirect)
                    <span class="text-xs text-gray-400 font-mono">+ www</span>
                    @endif
                    {{-- Type badge --}}
                    @if($domain->is_subdomain)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Sous-domaine</span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Domaine</span>
                    @endif
                    {{-- DNS badge --}}
                    <span id="dns-badge-{{ $domain->id }}"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $domain->dns_ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $domain->dns_ok ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                        <span id="dns-label-{{ $domain->id }}">{{ $domain->dns_ok ? 'DNS OK' : 'DNS non pointé' }}</span>
                    </span>
                    {{-- SSL badge --}}
                    @if($domain->ssl_enabled)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        🔒 SSL actif
                        @if($domain->ssl_expires_at)
                        <span class="text-green-500">· expire {{ $domain->ssl_expires_at->format('d/m/Y') }}</span>
                        @endif
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">HTTP</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-500 flex-wrap">
                    <span>→ {{ $domain->target_ip }}:{{ $domain->target_port }}</span>
                    <span class="text-gray-300">·</span>
                    @if($domain->type === 'vps' && $domain->virtualMachine)
                    <span>VPS : {{ $domain->virtualMachine->name }}</span>
                    @elseif($domain->type === 'hosting' && $domain->hostingAccount)
                    <span>Site : {{ $domain->hostingAccount->domain }}</span>
                    @endif
                    @if($domain->dns_checked_at)
                    <span class="text-gray-300">· vérifié {{ $domain->dns_checked_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                {{-- Re-check DNS --}}
                <button type="button"
                    onclick="checkDns({{ $domain->id }}, '{{ $domain->domain }}')"
                    class="text-xs text-gray-500 hover:text-indigo-600 px-2 py-1 rounded hover:bg-gray-50 transition">
                    Vérifier DNS
                </button>

                {{-- Enable SSL --}}
                @if(!$domain->ssl_enabled)
                @if($domain->dns_ok)
                <form method="POST" action="{{ route('client.domains.enable-ssl', $domain) }}">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 rounded hover:bg-indigo-50 transition">
                        Activer SSL
                    </button>
                </form>
                @else
                <span id="ssl-btn-{{ $domain->id }}" class="text-xs text-gray-300 px-2 py-1 cursor-not-allowed" title="Vérifiez d'abord le DNS">
                    Activer SSL
                </span>
                @endif
                @endif

                {{-- Delete --}}
                <button @click="confirmDelete = !confirmDelete"
                    class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded hover:bg-red-50 transition">
                    Supprimer
                </button>
            </div>
        </div>

        {{-- Delete confirm --}}
        <div x-show="confirmDelete" x-collapse class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-sm text-red-700 mb-2">Confirmer la suppression de <strong>{{ $domain->domain }}</strong> ? Le proxy NPM sera également supprimé.</p>
            <form method="POST" action="{{ route('client.domains.destroy', $domain) }}" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">
                    Confirmer la suppression
                </button>
                <button type="button" @click="confirmDelete = false" class="ml-2 text-xs text-gray-500 hover:underline">Annuler</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection

@push('scripts')
<script>
async function checkDns(id, domain) {
    const badge  = document.getElementById('dns-badge-' + id);
    const label  = document.getElementById('dns-label-' + id);
    const sslBtn = document.getElementById('ssl-btn-' + id);
    label.textContent = 'Vérification…';

    try {
        const res = await fetch('{{ route('client.domains.check-dns') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ domain }),
        });
        const data = await res.json();

        badge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ' +
            (data.ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700');
        badge.querySelector('span').className = 'w-1.5 h-1.5 rounded-full ' + (data.ok ? 'bg-green-500' : 'bg-amber-500');
        label.textContent = data.ok ? 'DNS OK' : 'DNS non pointé';

        if (data.ok && sslBtn) {
            sslBtn.outerHTML = `<form method="POST" action="{{ url('/client/domains') }}/${id}/ssl">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 rounded hover:bg-indigo-50 transition">Activer SSL</button>
            </form>`;
        }
    } catch(e) {
        label.textContent = 'Erreur';
    }
}
</script>
@endpush
