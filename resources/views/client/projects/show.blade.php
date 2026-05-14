@extends('layouts.app')
@section('title', $project->title)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('client.projects.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Mes projets</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $project->title }}</h1>
</div>

@foreach(['success','error'] as $t)
@if(session($t))
@php $cls = $t === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; @endphp
<div class="mb-4 px-4 py-3 {{ $cls }} border rounded-lg text-sm">{{ session($t) }}</div>
@endif
@endforeach

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <div class="lg:col-span-2 space-y-5">

        {{-- Progress --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-800 text-sm">Avancement global</h2>
                @php $pct = $project->progressPercent(); @endphp
                <span class="text-sm font-bold text-indigo-600">{{ $pct }}%</span>
            </div>
            <div class="bg-gray-100 rounded-full h-3 mb-5">
                <div class="bg-indigo-500 h-3 rounded-full transition-all" style="width:{{ $pct }}%"></div>
            </div>

            @if($project->steps && count($project->steps))
            <ol class="space-y-3">
                @foreach($project->steps as $i => $step)
                @php
                    $st = $step['status'] ?? 'pending';
                    $done = $st === 'completed';
                    $active = $st === 'in_progress';
                @endphp
                <li class="flex items-center gap-3">
                    <div class="shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center
                        {{ $done ? 'bg-green-500 border-green-500' : ($active ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-white') }}">
                        @if($done)
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @elseif($active)
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                        @endif
                    </div>
                    <span class="text-sm {{ $done ? 'text-gray-400 line-through' : ($active ? 'text-gray-900 font-medium' : 'text-gray-600') }}">
                        {{ $step['title'] }}
                    </span>
                    @if($active)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">En cours</span>
                    @endif
                </li>
                @endforeach
            </ol>
            @endif
        </div>

        {{-- Messages --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm">Messages</h3>
                <p class="text-xs text-gray-400 mt-0.5">Posez vos questions ou partagez vos retours sur ce projet.</p>
            </div>

            @if($project->messages->count())
            <div class="divide-y divide-gray-50">
                @foreach($project->messages as $msg)
                <div class="px-5 py-4 flex gap-3 {{ $msg->author === 'admin' ? 'bg-indigo-50/40' : '' }}">
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $msg->author === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $msg->author === 'admin' ? 'E' : strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-sm font-semibold {{ $msg->author === 'admin' ? 'text-indigo-700' : 'text-gray-800' }}">
                                {{ $msg->author === 'admin' ? 'Équipe' : 'Vous' }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $msg->body }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="px-5 py-6 text-sm text-gray-400 text-center">Aucun message pour l'instant.</p>
            @endif

            @if(! in_array($project->status, ['completed', 'cancelled']))
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                <form method="POST" action="{{ route('client.projects.message', $project) }}" class="flex gap-2">
                    @csrf
                    <textarea name="body" rows="2" required maxlength="2000" placeholder="Votre message…"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none">{{ old('body') }}</textarea>
                    <button type="submit"
                        class="self-end px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Envoyer
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Statut</h3>
            <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold {{ $project->statusColor() }}">
                {{ $project->statusLabel() }}
            </span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-sm space-y-2 text-gray-600">
            <div class="flex justify-between">
                <span class="text-gray-400">Référence</span>
                <span class="font-mono font-medium">#{{ $project->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Démarré le</span>
                <span>{{ $project->created_at->format('d/m/Y') }}</span>
            </div>
            @if($project->due_date)
            <div class="flex justify-between">
                <span class="text-gray-400">Échéance</span>
                <span class="{{ $project->due_date->isPast() && $project->status !== 'completed' ? 'text-red-500 font-medium' : '' }}">
                    {{ $project->due_date->format('d/m/Y') }}
                </span>
            </div>
            @endif
        </div>

        @if($project->description)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-2 text-sm">Description</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $project->description }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
