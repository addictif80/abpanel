@extends('layouts.app')
@section('title', 'Tarifs')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tarifs</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $plans->count() }} plan(s)</p>
    </div>
    <a href="{{ route('admin.plans.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau plan
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
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Nom</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prix</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Ressources</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Stripe</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Ordre</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($plans as $plan)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $plan->name }}</div>
                        <div class="text-xs text-gray-400">{{ $plan->slug }}</div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $plan->type === 'vm' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ strtoupper($plan->type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $plan->formattedPrice() }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        @if($plan->cores || $plan->memory_mb || $plan->disk_gb)
                            {{ $plan->cores ? $plan->cores . ' vCPU' : '' }}
                            {{ $plan->memory_mb ? ' · ' . ($plan->memory_mb >= 1024 ? round($plan->memory_mb/1024, 0) . ' GB RAM' : $plan->memory_mb . ' MB RAM') : '' }}
                            {{ $plan->disk_gb ? ' · ' . $plan->disk_gb . ' GB' : '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs font-mono text-gray-400">
                        {{ $plan->stripe_price_id ? substr($plan->stripe_price_id, 0, 16) . '…' : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $plan->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400">{{ $plan->sort_order }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="text-indigo-600 hover:underline text-xs mr-3">Modifier</a>
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline"
                              onsubmit="return confirm('Supprimer ce plan ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:underline text-xs">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">Aucun plan configuré</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
