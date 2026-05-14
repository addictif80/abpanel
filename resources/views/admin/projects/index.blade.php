@extends('layouts.app')
@section('title', 'Projets en cours')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Projets en cours</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $projects->total() }} projet(s)</p>
    </div>
    <a href="{{ route('admin.projects.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau projet
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Projet</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Avancement</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Échéance</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($projects as $project)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="font-medium text-gray-800">{{ $project->title }}</div>
                    @if($project->description)
                    <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $project->description }}</div>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <a href="{{ route('admin.clients.show', $project->user) }}" class="text-indigo-600 hover:underline text-sm">
                        {{ $project->user->full_name }}
                    </a>
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $project->statusColor() }}">
                        {{ $project->statusLabel() }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    @php $pct = $project->progressPercent(); @endphp
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5 w-24">
                            <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ $pct }}%</span>
                    </div>
                </td>
                <td class="px-5 py-3 text-xs text-gray-400">
                    @if($project->due_date)
                        <span class="{{ $project->due_date->isPast() && $project->status !== 'completed' ? 'text-red-500 font-medium' : '' }}">
                            {{ $project->due_date->format('d/m/Y') }}
                        </span>
                    @else —
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.projects.show', $project) }}" class="text-gray-500 hover:text-indigo-600 text-xs">Gérer →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Aucun projet</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($projects->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $projects->links() }}</div>
    @endif
</div>
@endsection
