@php
$fieldsByType = [
    'proxmox' => [
        ['name' => 'host', 'label' => 'URL (ex: https://proxmox.exemple.fr:8006)', 'type' => 'url', 'required' => true],
        ['name' => 'user', 'label' => 'Utilisateur', 'type' => 'text', 'required' => true, 'default' => 'root'],
        ['name' => 'password', 'label' => 'Mot de passe', 'type' => 'password', 'required' => !isset($server)],
        ['name' => 'realm', 'label' => 'Realm', 'type' => 'text', 'required' => true, 'default' => 'pam'],
        ['name' => 'default_node', 'label' => 'Nœud par défaut (achats en ligne)', 'type' => 'text', 'required' => false],
        ['name' => 'default_storage', 'label' => 'Stockage par défaut (achats en ligne)', 'type' => 'text', 'required' => false],
    ],
    'cyberpanel' => [
        ['name' => 'host', 'label' => 'URL (ex: https://cyberpanel.exemple.fr:8090)', 'type' => 'url', 'required' => true],
        ['name' => 'user', 'label' => 'Utilisateur admin', 'type' => 'text', 'required' => true, 'default' => 'admin'],
        ['name' => 'api_token', 'label' => 'Token API (Users → API Access dans CyberPanel)', 'type' => 'password', 'required' => !isset($server)],
        ['name' => 'tailscale_ip', 'label' => 'IP Tailscale (pour les domaines)', 'type' => 'text', 'required' => false],
    ],
    'synology' => [
        ['name' => 'host', 'label' => 'URL (ex: https://nas.exemple.fr:5001)', 'type' => 'url', 'required' => true],
        ['name' => 'user', 'label' => 'Utilisateur', 'type' => 'text', 'required' => true],
        ['name' => 'password', 'label' => 'Mot de passe', 'type' => 'password', 'required' => !isset($server)],
        ['name' => 'shared_folder', 'label' => 'Dossier partagé (quota cloud)', 'type' => 'text', 'required' => false],
    ],
];
$labels = ['proxmox' => 'Proxmox', 'cyberpanel' => 'CyberPanel', 'synology' => 'Synology'];
$type = $type ?? $server->type;
$fields = $fieldsByType[$type];
@endphp

@extends('layouts.app')
@section('title', isset($server) ? 'Modifier le serveur' : 'Nouveau serveur ' . $labels[$type])
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="max-w-xl mx-auto">
    <a href="{{ route('admin.infra-servers.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Serveurs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1 mb-6">
        {{ isset($server) ? 'Modifier le serveur ' . $server->label : 'Nouveau serveur ' . $labels[$type] }}
    </h1>

    @if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ isset($server) ? route('admin.infra-servers.update', $server) : route('admin.infra-servers.store') }}"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        @csrf
        @if(isset($server)) @method('PUT') @endif
        <input type="hidden" name="type" value="{{ $type }}">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Libellé <span class="text-red-500">*</span></label>
            <input type="text" name="label" value="{{ old('label', $server->label ?? '') }}" required
                placeholder="ex: Proxmox Paris 1"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        @foreach($fields as $f)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $f['label'] }} @if($f['required'])<span class="text-red-500">*</span>@else<span class="text-xs font-normal text-gray-400">(optionnel)</span>@endif
            </label>
            <input type="{{ $f['type'] }}" name="{{ $f['name'] }}"
                value="{{ old($f['name'], ($server ?? null)?->get($f['name'], $f['default'] ?? '') ?? $f['default'] ?? '') }}"
                {{ $f['required'] ? 'required' : '' }}
                @if($f['type'] === 'password' && isset($server)) placeholder="{{ $f['name'] === 'api_token' ? 'Laisser vide pour conserver le token actuel' : 'Laisser vide pour conserver le mot de passe actuel' }}" @endif
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        @endforeach

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $server->is_default ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700">Serveur par défaut pour ce type (utilisé pour les achats en ligne et quand aucun serveur n'est explicitement choisi)</span>
        </label>

        <div class="pt-2 flex items-center gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Enregistrer
            </button>
            <a href="{{ route('admin.infra-servers.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
        </div>
    </form>
</div>
@endsection
