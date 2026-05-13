@extends('layouts.app')
@section('title', 'Modèles de devis')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Devis</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Modèles de devis</h1>
    </div>
    <a href="{{ route('admin.quotes.create') }}"
       class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau devis
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Nom du modèle</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Objet</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Lignes</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Créé le</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($templates as $template)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $template->template_name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $template->subject ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $template->items->count() }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $template->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.quotes.create') }}?template={{ $template->id }}" class="text-indigo-600 hover:underline text-xs">Utiliser</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-gray-400">Aucun modèle. Créez un devis et sauvegardez-le comme modèle.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($templates->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $templates->links() }}</div>
    @endif
</div>
@endsection
