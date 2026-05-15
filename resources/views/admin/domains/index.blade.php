@extends('layouts.app')
@section('title', 'Domaines clients')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Domaines clients</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $domains->total() }} domaine(s) configuré(s)</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domaine</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cible</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">DNS</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SSL</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Créé le</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($domains as $domain)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <span class="font-mono font-medium text-gray-900">{{ $domain->domain }}</span>
                    @if($domain->www_redirect)
                    <span class="ml-1 text-xs text-gray-400">+www</span>
                    @endif
                    <div class="mt-0.5">
                        @if($domain->is_subdomain)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Sous-domaine</span>
                        @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Domaine</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800">{{ $domain->user->full_name }}</div>
                    <div class="text-xs text-gray-400">{{ $domain->user->email }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-600">
                    {{ $domain->forward_scheme }}://{{ $domain->target_ip }}:{{ $domain->target_port }}
                    <div class="text-gray-400">
                        @if($domain->type === 'vps' && $domain->virtualMachine)
                            VPS: {{ $domain->virtualMachine->name }}
                        @elseif($domain->type === 'hosting' && $domain->hostingAccount)
                            Site: {{ $domain->hostingAccount->domain }}
                        @else
                            {{ $domain->type }}
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3">
                    @if($domain->dns_ok)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> OK
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Non pointé
                    </span>
                    @endif
                    @if($domain->dns_checked_at)
                    <div class="text-xs text-gray-400 mt-0.5">{{ $domain->dns_checked_at->diffForHumans() }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($domain->ssl_enabled)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        🔒 Actif
                    </span>
                    @if($domain->ssl_expires_at)
                    <div class="text-xs text-gray-400 mt-0.5">expire {{ $domain->ssl_expires_at->format('d/m/Y') }}</div>
                    @endif
                    @else
                    <span class="text-xs text-gray-400">HTTP</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $domain->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route('admin.domains.destroy', $domain) }}"
                          onsubmit="return confirm('Supprimer le domaine {{ $domain->domain }} et son proxy NPM ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium">Supprimer</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">Aucun domaine configuré.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($domains->hasPages())
<div class="mt-4">{{ $domains->links() }}</div>
@endif
@endsection
