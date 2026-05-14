@extends('layouts.app')
@section('title', 'Nouveau projet')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.projects.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Projets</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouveau projet</h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.projects.store') }}"
      x-data="projectForm({{ json_encode(old('steps', $defaultSteps)) }})">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-5">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Informations</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} ({{ $client->email }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre du projet <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="pending"     {{ old('status') === 'pending'     ? 'selected' : '' }}>En attente</option>
                            <option value="in_progress" {{ old('status','in_progress') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                            <option value="review"      {{ old('status') === 'review'      ? 'selected' : '' }}>En révision</option>
                            <option value="completed"   {{ old('status') === 'completed'   ? 'selected' : '' }}>Terminé</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date limite</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Steps --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">Étapes du projet</h2>
                    <button type="button" @click="addStep()"
                        class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">
                        + Ajouter une étape
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="flex items-center gap-3">
                            <input type="text" :name="'steps['+i+'][title]'" x-model="step.title" required placeholder="Nom de l'étape"
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
        </div>

        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3 sticky top-6">
                <button type="submit"
                    class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Créer le projet
                </button>
                <a href="{{ route('admin.projects.index') }}"
                    class="block w-full py-2.5 text-center text-sm text-gray-500 hover:underline">
                    Annuler
                </a>
            </div>
        </div>
    </div>
</form>

<script>
function projectForm(initialSteps) {
    return {
        steps: initialSteps,
        addStep() {
            this.steps.push({ title: '', status: 'pending' });
        }
    };
}
</script>
@endsection
