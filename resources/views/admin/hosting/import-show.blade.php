@extends('layouts.app')
@section('title', 'Importer ' . $siteInfo['domain'])
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.hosting.import.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Importer un hébergement</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">
        Importer « {{ $siteInfo['domain'] }} »
    </h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.hosting.import.store', urlencode($siteInfo['domain'])) }}" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Assignation</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="user_id" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un client —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} — {{ $client->email }}
                            @if($client->company) ({{ $client->company }})@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (€) <span class="text-red-500">*</span></label>
                    <input type="number" name="monthly_price" value="{{ old('monthly_price', '0.00') }}" min="0" step="0.01" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de renouvellement</label>
                    <input type="date" name="next_renewal_at" value="{{ old('next_renewal_at') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Informations hébergement <span class="text-xs font-normal text-gray-400">(pré-remplies depuis CyberPanel)</span></h2>

                <input type="hidden" name="cyberpanel_username" value="{{ old('cyberpanel_username', $siteInfo['owner']) }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Domaine</label>
                        <input type="text" value="{{ $siteInfo['domain'] }}" readonly
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur CyberPanel</label>
                        <input type="text" value="{{ $siteInfo['owner'] }}" readonly
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Offre CyberPanel</label>
                        <input type="text" name="plan" value="{{ old('plan', $siteInfo['package']) }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Espace disque alloué (MB)</label>
                        <input type="number" name="disk_mb" value="{{ old('disk_mb', $siteInfo['disk_mb']) }}" min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Importer et assigner
                </button>
                <a href="{{ route('admin.hosting.import.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
            </div>
        </form>
    </div>

    {{-- CyberPanel info card --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Informations CyberPanel</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Domaine</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $siteInfo['domain'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Propriétaire</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $siteInfo['owner'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Offre</dt>
                    <dd class="font-medium text-gray-800">{{ $siteInfo['package'] ?: '—' }}</dd>
                </div>
                @if($siteInfo['disk_mb'])
                <div class="flex justify-between">
                    <dt class="text-gray-500">Disque alloué</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $siteInfo['disk_mb'] >= 1024 ? round($siteInfo['disk_mb'] / 1024, 1) . ' GB' : $siteInfo['disk_mb'] . ' MB' }}
                    </dd>
                </div>
                @endif
                @if($siteInfo['email'])
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email admin</dt>
                    <dd class="font-medium text-gray-800 text-xs">{{ $siteInfo['email'] }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

</div>
@endsection
