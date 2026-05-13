@extends('layouts.app')
@section('title', 'Catalogue produits')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Catalogue produits</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $products->total() }} produit(s)</p>
    </div>
    <a href="{{ route('admin.products.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau produit
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 border-b border-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, référence..."
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">Filtrer</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Référence</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Nom</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prix unitaire</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Unité</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">TVA</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Masqué pros</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $product->reference ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $product->name }}</div>
                        @if($product->description)
                        <div class="text-xs text-gray-400 truncate max-w-xs">{{ Str::limit($product->description, 60) }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($product->unit_price, 2) }} €</td>
                    <td class="px-5 py-3 text-gray-500">{{ $product->unit }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $product->tax_rate > 0 ? $product->tax_rate . '%' : 'Non applicable' }}</td>
                    <td class="px-5 py-3">
                        @if($product->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Actif</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($product->hidden_from_pro)
                            <span title="Masqué aux clients professionnels" class="text-amber-500">●</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right space-x-3">
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-indigo-600 hover:underline text-xs">Modifier</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline"
                            onsubmit="return confirm('Supprimer ce produit ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline text-xs">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">Aucun produit. <a href="{{ route('admin.products.create') }}" class="text-indigo-600 hover:underline">Créer le premier.</a></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $products->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
