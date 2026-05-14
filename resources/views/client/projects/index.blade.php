@extends('layouts.app')
@section('title', 'Mes projets')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mes projets</h1>
    <p class="text-gray-500 text-sm mt-1">Suivez l'avancement de vos projets en cours.</p>
</div>

<div class="space-y-4">
    @forelse($projects as $project)
    <a href="{{ route('client.projects.show', $project) }}"
       class="block bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="font-semibold text-gray-900 truncate">{{ $project->title }}</h2>
                    <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $project->statusColor() }}">
                        {{ $project->statusLabel() }}
                    </span>
                </div>
                @if($project->description)
                <p class="text-sm text-gray-500 truncate">{{ $project->description }}</p>
                @endif
            </div>
            @if($project->due_date)
            <div class="text-right shrink-0">
                <div class="text-xs text-gray-400">Échéance</div>
                <div class="text-sm font-medium {{ $project->due_date->isPast() && $project->status !== 'completed' ? 'text-red-500' : 'text-gray-700' }}">
                    {{ $project->due_date->format('d/m/Y') }}
                </div>
            </div>
            @endif
        </div>
        @php $pct = $project->progressPercent(); @endphp
        <div class="mt-4 flex items-center gap-3">
            <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width:{{ $pct }}%"></div>
            </div>
            <span class="text-xs font-medium text-gray-500 w-8 text-right">{{ $pct }}%</span>
        </div>
    </a>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-12 text-center text-gray-400">
        <p class="text-4xl mb-3">🚀</p>
        <p class="text-sm">Aucun projet en cours. Contactez-nous pour démarrer.</p>
    </div>
    @endforelse
</div>

@if($projects->hasPages())
<div class="mt-4">{{ $projects->links() }}</div>
@endif
@endsection
