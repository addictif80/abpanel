@extends('layouts.app')

@section('title', 'Bac à sable')

@section('sidebar')
    <x-admin-sidebar />
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bac à sable</h1>
    <p class="text-gray-500 text-sm mt-1">Testez toutes les fonctionnalités du panel avec des données de démonstration.</p>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif
@if(session('info'))
<div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">{{ session('info') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: controls --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Demo data status --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Données de démonstration</h2>

            @if($stats['has_demo_data'])
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                @foreach([
                    ['label' => 'Clients demo', 'value' => $stats['demo_clients']],
                    ['label' => 'VMs', 'value' => $stats['demo_vms']],
                    ['label' => 'Hébergements', 'value' => $stats['demo_hosting']],
                    ['label' => 'Devis', 'value' => $stats['demo_quotes']],
                    ['label' => 'Factures', 'value' => $stats['demo_invoices']],
                    ['label' => 'Projets', 'value' => $stats['demo_projects']],
                    ['label' => 'Tickets', 'value' => $stats['demo_tickets']],
                ] as $stat)
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-indigo-600">{{ $stat['value'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>

            <div class="flex gap-3">
                <form method="POST" action="{{ route('admin.sandbox.seed') }}">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Re-seeder les données
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.sandbox.reset') }}"
                      onsubmit="return confirm('Supprimer toutes les données de démonstration ? Cette action est irréversible.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 border border-red-200 transition">
                        Supprimer les données demo
                    </button>
                </form>
            </div>

            @else
            <p class="text-sm text-gray-500 mb-4">Aucune donnée de démonstration présente. Cliquez ci-dessous pour créer 3 clients de test avec VMs, hébergements, devis, factures, projets et tickets.</p>
            <form method="POST" action="{{ route('admin.sandbox.seed') }}">
                @csrf
                <button type="submit"
                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Créer les données de démonstration
                </button>
            </form>
            @endif
        </div>

        {{-- Commandes artisan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-1">Commandes (dry-run)</h2>
            <p class="text-sm text-gray-500 mb-4">Testez les commandes planifiées sans déclencher d'envoi réel.</p>

            <form method="POST" action="{{ route('admin.sandbox.command') }}" class="flex items-center gap-3">
                @csrf
                <select name="command" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="reminders:send">reminders:send (relances, expirations, récurrences)</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm font-semibold rounded-lg hover:bg-gray-800 transition">
                    Exécuter (dry-run)
                </button>
            </form>

            @if(session('command_output'))
            <div class="mt-4">
                <p class="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Sortie — {{ session('command_run') }} --dry-run</p>
                <pre class="bg-gray-900 text-green-400 text-xs rounded-lg p-4 overflow-x-auto whitespace-pre-wrap">{{ session('command_output') }}</pre>
            </div>
            @endif
        </div>

        {{-- Fonctionnalités à tester --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Checklist des fonctionnalités</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach([
                    ['Tableau de bord admin', route('admin.dashboard')],
                    ['Liste des clients', route('admin.clients.index')],
                    ['Machines virtuelles', route('admin.vms.index')],
                    ['Hébergements web', route('admin.hosting.index')],
                    ['Devis', route('admin.quotes.index')],
                    ['Factures', route('admin.invoices.index')],
                    ['Projets en cours', route('admin.projects.index')],
                    ['Tickets de support', route('admin.tickets.index')],
                    ['Notifications (admin)', route('notifications.index')],
                    ['Paramètres', route('admin.settings.index')],
                    ['Templates mail', route('admin.mail-templates.index')],
                    ['Newsletter', route('admin.newsletter.index')],
                ] as [$label, $url])
                <a href="{{ $url }}" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-100 hover:bg-indigo-50 hover:border-indigo-200 text-sm text-gray-700 hover:text-indigo-700 transition group">
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Right: demo accounts --}}
    <div class="space-y-5">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Comptes de démonstration</h2>
            <p class="text-xs text-gray-400 mb-4">Mot de passe : <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $stats['demo_password'] }}</code></p>

            <div class="space-y-3">
                @foreach($stats['demo_emails'] as $email)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $email }}</p>
                        <p class="text-xs text-gray-400">Client demo</p>
                    </div>
                    @php $demoUser = \App\Models\User::where('email', $email)->first(); @endphp
                    @if($demoUser)
                    <form method="POST" action="{{ route('admin.clients.impersonate', $demoUser) }}">
                        @csrf
                        <button type="submit" class="text-xs px-2 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition font-medium">
                            Se connecter
                        </button>
                    </form>
                    @else
                    <span class="text-xs text-gray-400">Non créé</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Liens portail client</h2>
            <div class="space-y-2">
                @foreach([
                    ['Dashboard', route('client.dashboard')],
                    ['Mes VMs', route('client.vms.index')],
                    ['Mon hébergement', route('client.hosting.index')],
                    ['Mes devis', route('client.quotes.index')],
                    ['Ma facturation', route('client.billing.index')],
                    ['Mes projets', route('client.projects.index')],
                    ['Mes tickets', route('client.tickets.index')],
                    ['Notifications', route('notifications.index')],
                    ['Mon profil', route('client.profile')],
                ] as [$label, $url])
                <a href="{{ $url }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-indigo-600 transition">
                    <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2">Attention</p>
            <p class="text-xs text-amber-600">Le bac à sable utilise les mêmes données et emails que la production. Les emails de test se terminent par <code>@demo.test</code> et ne seront pas envoyés si SMTP est configuré correctement.</p>
        </div>

    </div>
</div>
@endsection
