@extends('layouts.app')
@section('title', 'Ajouter un domaine')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="{{ route('client.domains.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Mes domaines</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1 mb-6">Ajouter un domaine</h1>

    @if($npmPublicIp)
    <div class="mb-5 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <strong>Avant de continuer :</strong> créez un enregistrement DNS de type <strong>A</strong> pointant
        <code class="bg-blue-100 px-1 rounded font-mono">votre-domaine.com</code> vers
        <strong class="font-mono">{{ $npmPublicIp }}</strong>.
        La vérification DNS se fait automatiquement lors de l'ajout.
    </div>
    @endif

    @if ($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 space-y-1">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('client.domains.store') }}" class="space-y-5"
          x-data="{
            type: '{{ old('type', 'vps') }}',
            vpsIps: {{ Js::from($vps->mapWithKeys(fn($v) => [$v->id => $v->tailscale_ip ?: ''])) }},
            port: '{{ old('target_port', 80) }}',
            scheme: '{{ old('forward_scheme', 'http') }}',
            cyberpanelIp: '{{ $cyberpanelIp }}',
            get targetPlaceholder() {
                return this.type === 'hosting' ? this.cyberpanelIp || '—' : '';
            }
          }">
        @csrf

        {{-- Domaine --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">Domaine / sous-domaine</h2>
            <div>
                <input type="text" name="domain" value="{{ old('domain') }}"
                    placeholder="app.monsite.com ou monsite.com"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    required>
                <p class="text-xs text-gray-400 mt-1">Sans http:// ni slash final.</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="www_redirect" value="1" {{ old('www_redirect') ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Inclure aussi <code class="text-xs bg-gray-100 px-1 rounded">www.</code> (redirect www ↔ domaine racine)</span>
            </label>
        </div>

        {{-- Type de cible --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">Cible</h2>

            <div class="grid grid-cols-2 gap-3">
                <label class="relative flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                    :class="type === 'vps' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="type" value="vps" x-model="type" class="sr-only">
                    <svg class="w-5 h-5" :class="type === 'vps' ? 'text-indigo-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                    <div>
                        <div class="text-sm font-semibold" :class="type === 'vps' ? 'text-indigo-700' : 'text-gray-700'">Mon VPS</div>
                        <div class="text-xs text-gray-400">IP Tailscale + port</div>
                    </div>
                </label>
                <label class="relative flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                    :class="type === 'hosting' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="type" value="hosting" x-model="type" class="sr-only">
                    <svg class="w-5 h-5" :class="type === 'hosting' ? 'text-indigo-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                    </svg>
                    <div>
                        <div class="text-sm font-semibold" :class="type === 'hosting' ? 'text-indigo-700' : 'text-gray-700'">Mon hébergement</div>
                        <div class="text-xs text-gray-400">CyberPanel, port 80</div>
                    </div>
                </label>
            </div>

            {{-- VPS fields --}}
            <div x-show="type === 'vps'" class="space-y-3">
                @if($vps->isEmpty())
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    Vous n'avez aucun VPS actif avec une IP Tailscale. Démarrez un VPS ou attendez l'attribution de son IP.
                </p>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">VPS <span class="text-red-500">*</span></label>
                    <select name="vps_id" x-on:change="port = 80"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Choisir un VPS —</option>
                        @foreach($vps as $v)
                        <option value="{{ $v->id }}" {{ old('vps_id') == $v->id ? 'selected' : '' }}>
                            {{ $v->name }} — {{ $v->tailscale_ip ?: 'IP manquante' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Port <span class="text-red-500">*</span></label>
                    <input type="number" name="target_port" x-model="port" min="1" max="65535"
                        placeholder="ex: 3000, 8080, 443"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Port sur lequel écoute votre application (hors ports système comme 22, 3306…).</p>
                </div>
            </div>

            {{-- Hosting fields --}}
            <div x-show="type === 'hosting'" class="space-y-3">
                @if($hosting->isEmpty())
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    Vous n'avez aucun hébergement actif.
                </p>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hébergement</label>
                    <select name="hosting_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($hosting as $h)
                        <option value="{{ $h->id }}" {{ old('hosting_id') == $h->id ? 'selected' : '' }}>{{ $h->domain }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP cible</label>
                    <input type="text" :value="cyberpanelIp || 'Non configurée'" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 font-mono">
                    <p class="text-xs text-gray-400 mt-1">IP Tailscale de CyberPanel (configurée par l'administrateur). Port : 80.</p>
                </div>
                <input type="hidden" name="target_port" value="80">
            </div>
        </div>

        {{-- Protocole --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-gray-800">Protocole vers le backend</h2>
            <p class="text-sm text-gray-500 -mt-1">Comment NPM doit contacter votre service en interne.</p>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="radio" name="forward_scheme" value="http" x-model="scheme" class="text-indigo-600 focus:ring-indigo-500">
                    HTTP <span class="text-gray-400 text-xs">(recommandé pour la plupart des cas)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="radio" name="forward_scheme" value="https" x-model="scheme" class="text-indigo-600 focus:ring-indigo-500">
                    HTTPS <span class="text-gray-400 text-xs">(si votre service utilise déjà SSL)</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Créer le proxy et vérifier le DNS
            </button>
            <a href="{{ route('client.domains.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
        </div>
    </form>
</div>
@endsection
