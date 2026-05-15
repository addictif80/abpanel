@extends('layouts.app')
@section('title', 'Codes promo')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Codes promo</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $codes->total() }} code(s)</p>
    </div>
    <a href="{{ route('admin.promo-codes.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Créer un code
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Valeur</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Min.</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Utilisations</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Expiration</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($codes as $code)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono font-semibold text-gray-900">{{ $code->code }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $code->discount_type === 'percent' ? 'Pourcentage' : 'Montant fixe' }}</td>
                    <td class="px-5 py-3 text-gray-900 font-medium">
                        @if($code->discount_type === 'percent')
                            {{ rtrim(rtrim(number_format($code->discount_value, 2), '0'), '.') }}%
                        @else
                            {{ number_format($code->discount_value, 2) }}€
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        {{ $code->min_amount ? number_format($code->min_amount, 2) . '€' : '—' }}
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        {{ $code->used_count }} / {{ $code->max_uses ?? '∞' }}
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        @if($code->expires_at)
                            <span class="{{ $code->expires_at->isPast() ? 'text-red-500' : '' }}">
                                {{ $code->expires_at->format('d/m/Y') }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if($code->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Actif</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Inactif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2 justify-end">
                            <form method="POST" action="{{ route('admin.promo-codes.toggle', $code) }}">
                                @csrf
                                <button type="submit" class="text-xs text-gray-500 hover:text-indigo-600 transition">
                                    {{ $code->is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.promo-codes.destroy', $code) }}"
                                onsubmit="return confirm('Supprimer ce code promo ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">Aucun code promo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($codes->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $codes->links() }}
    </div>
    @endif
</div>
@endsection
