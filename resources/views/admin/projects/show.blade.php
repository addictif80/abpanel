@extends('layouts.app')
@section('title', $project->title)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('admin.projects.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Projets</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $project->title }}</h1>
        <p class="text-gray-500 text-sm mt-0.5">{{ $project->user->full_name }}</p>
    </div>
    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
          onsubmit="return confirm('Supprimer ce projet ?')">
        @csrf @method('DELETE')
        <button class="px-4 py-2 bg-white border border-red-200 text-red-600 text-sm rounded-lg hover:bg-red-50 transition">
            Supprimer
        </button>
    </form>
</div>

@foreach(['success','error'] as $t)
@if(session($t))
@php $cls = $t === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; @endphp
<div class="mb-4 px-4 py-3 {{ $cls }} border rounded-lg text-sm">{{ session($t) }}</div>
@endif
@endforeach

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: edit form + messages --}}
    <div class="lg:col-span-2 space-y-5">

        <form method="POST" action="{{ route('admin.projects.update', $project) }}"
              x-data="projectForm({{ json_encode($project->steps ?? []) }})">
            @csrf @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Informations</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                    <input type="text" name="title" value="{{ old('title', $project->title) }}" required maxlength="200"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none">{{ old('description', $project->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            @foreach(['pending'=>'En attente','in_progress'=>'En cours','review'=>'En révision','completed'=>'Terminé','cancelled'=>'Annulé'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('status', $project->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date limite</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="notify_client" value="1" class="rounded">
                    Notifier le client de cette mise à jour
                </label>
            </div>

            {{-- Steps --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">Étapes</h2>
                    <button type="button" @click="addStep()"
                        class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">
                        + Ajouter
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="step.status === 'completed' ? 'bg-green-500 border-green-500' : (step.status === 'in_progress' ? 'border-indigo-500' : 'border-gray-300')">
                                <svg x-show="step.status === 'completed'" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <input type="text" :name="'steps['+i+'][title]'" x-model="step.title" required placeholder="Étape"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <select :name="'steps['+i+'][status]'" x-model="step.status"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="pending">En attente</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                            </select>
                            <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="steps.length === 0" class="text-sm text-gray-400 py-2">Aucune étape définie.</p>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Enregistrer les modifications
                </button>
            </div>
        </form>

        {{-- Messages --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Messages</h3>
                <span class="text-xs text-gray-400">{{ $project->messages->count() }} message(s)</span>
            </div>
            @if($project->messages->count())
            <div class="divide-y divide-gray-50">
                @foreach($project->messages as $msg)
                <div class="px-5 py-4 flex gap-3 {{ $msg->author === 'admin' ? 'bg-indigo-50/40' : '' }}">
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $msg->author === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $msg->author === 'admin' ? 'A' : strtoupper(substr($project->user->first_name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-sm font-semibold {{ $msg->author === 'admin' ? 'text-indigo-700' : 'text-gray-800' }}">
                                {{ $msg->author === 'admin' ? 'Équipe' : $project->user->full_name }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $msg->body }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="px-5 py-6 text-sm text-gray-400 text-center">Aucun message.</p>
            @endif
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                <form method="POST" action="{{ route('admin.projects.message', $project) }}" class="flex gap-2">
                    @csrf
                    <textarea name="body" rows="2" required maxlength="2000" placeholder="Répondre au client…"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                    <button type="submit"
                        class="self-end px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Avancement</h3>
            @php $pct = $project->progressPercent(); @endphp
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width:{{ $pct }}%"></div>
                </div>
                <span class="text-sm font-bold text-gray-700">{{ $pct }}%</span>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $project->statusColor() }}">
                {{ $project->statusLabel() }}
            </span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Client</h3>
            <div class="text-sm">
                <div class="font-medium text-gray-800">{{ $project->user->full_name }}</div>
                <div class="text-gray-400">{{ $project->user->email }}</div>
                @if($project->user->company)<div class="text-gray-500">{{ $project->user->company }}</div>@endif
            </div>
            <a href="{{ route('admin.clients.show', $project->user) }}"
               class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Voir le profil client →</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-sm space-y-2">
            <div class="flex justify-between text-gray-600">
                <span class="text-gray-400">Créé le</span>
                <span>{{ $project->created_at->format('d/m/Y') }}</span>
            </div>
            @if($project->due_date)
            <div class="flex justify-between text-gray-600">
                <span class="text-gray-400">Échéance</span>
                <span class="{{ $project->due_date->isPast() && $project->status !== 'completed' ? 'text-red-500 font-medium' : '' }}">
                    {{ $project->due_date->format('d/m/Y') }}
                </span>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function projectForm(initialSteps) {
    return {
        steps: initialSteps,
        addStep() { this.steps.push({ title: '', status: 'pending' }); }
    };
}
</script>
@endsection
