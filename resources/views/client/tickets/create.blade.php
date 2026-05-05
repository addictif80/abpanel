@extends('layouts.app')
@section('title', 'Nouveau ticket')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('client.tickets.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Support</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Ouvrir un ticket</h1>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('client.tickets.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sujet *</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="Décrivez votre problème en une phrase"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('subject') border-red-400 @enderror">
            @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Choisir —</option>
                    <option value="vm" {{ old('category') === 'vm' ? 'selected' : '' }}>Machine virtuelle</option>
                    <option value="hosting" {{ old('category') === 'hosting' ? 'selected' : '' }}>Hébergement web</option>
                    <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Facturation</option>
                    <option value="network" {{ old('category') === 'network' ? 'selected' : '' }}>Réseau / DNS</option>
                    <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priorité *</label>
                <select name="priority" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Basse</option>
                    <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normale</option>
                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Haute</option>
                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea name="message" rows="6" required placeholder="Décrivez votre problème en détail..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
            @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Envoyer le ticket
            </button>
            <a href="{{ route('client.tickets.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
