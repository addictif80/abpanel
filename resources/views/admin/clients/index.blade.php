@extends('layouts.app')
@section('title', 'Clients')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $clients->total() }} client(s)</p>
    </div>
    <a href="{{ route('admin.clients.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau client
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 border-b border-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou email..."
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">Rechercher</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">VMs</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Sites</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Tickets</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Inscrit le</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $client->full_name }}</div>
                        <div class="text-xs text-gray-400">{{ $client->email }}</div>
                        @if($client->company)
                            <div class="text-xs text-gray-400">{{ $client->company }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $client->virtual_machines_count }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $client->hosting_accounts_count }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $client->tickets_count }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $client->is_active ? 'Actif' : 'Désactivé' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $client->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.clients.show', $client) }}" class="text-indigo-600 hover:underline text-xs mr-3">Voir</a>
                        <a href="{{ route('admin.clients.edit', $client) }}" class="text-gray-500 hover:underline text-xs">Modifier</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucun client</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $clients->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
