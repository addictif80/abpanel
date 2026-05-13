@extends('layouts.app')
@section('title', 'Mes devis')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mes devis</h1>
    <p class="text-gray-500 text-sm mt-1">Consultez et répondez à vos devis.</p>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Objet</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Expiration</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($quotes as $quote)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs font-medium text-gray-800">{{ $quote->number }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $quote->subject ?: '—' }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($quote->total, 2) }} {{ $quote->currency }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $quote->statusColor() }}">
                            {{ $quote->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs {{ $quote->isExpired() ? 'text-red-500' : 'text-gray-400' }}">
                        {{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('client.quotes.show', $quote) }}" class="text-indigo-600 hover:underline text-xs font-medium">Voir</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-400">Aucun devis pour le moment.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quotes->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $quotes->links() }}</div>
    @endif
</div>
@endsection
