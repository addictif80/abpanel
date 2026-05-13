@extends('layouts.app')
@section('title', 'Avoirs')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Avoirs</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $creditNotes->total() }} avoir(s)</p>
    </div>
    <a href="{{ route('admin.credit-notes.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouvel avoir
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 border-b border-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Numéro, client..."
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">Filtrer</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Facture liée</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Créé le</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($creditNotes as $cn)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs font-medium text-gray-800">{{ $cn->number }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $cn->user?->full_name }}</div>
                        <div class="text-xs text-gray-400">{{ $cn->user?->email }}</div>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs text-indigo-600">
                        <a href="{{ route('admin.invoices.show', $cn->invoice) }}" class="hover:underline">{{ $cn->invoice->number }}</a>
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($cn->amount, 2) }} {{ $cn->currency }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cn->statusColor() }}">
                            {{ $cn->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $cn->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.credit-notes.show', $cn) }}" class="text-indigo-600 hover:underline text-xs">Voir</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucun avoir</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($creditNotes->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $creditNotes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
