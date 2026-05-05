@extends('layouts.app')
@section('title', $list->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $list->name }}</h1>
        <p class="text-gray-500 text-sm">{{ $subscribers->total() }} abonné(s)</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 text-sm mb-3">Ajouter un abonné</h2>
        <form method="POST" action="{{ route('admin.newsletter.lists.show', $list) }}" class="space-y-3">
            @csrf
            <input type="email" name="email" required placeholder="Email"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <input type="text" name="first_name" placeholder="Prénom (optionnel)"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Ajouter
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prénom</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Inscrit le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($subscribers as $sub)
                <tr>
                    <td class="px-5 py-3 text-gray-800">{{ $sub->email }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $sub->first_name ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sub->status === 'subscribed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $sub->status === 'subscribed' ? 'Abonné' : 'Désabonné' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sub->subscribed_at?->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Aucun abonné</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($subscribers->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">{{ $subscribers->links() }}</div>
        @endif
    </div>
</div>
@endsection
