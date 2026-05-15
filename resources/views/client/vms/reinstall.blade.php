@extends('layouts.app')
@section('title', 'Réinstaller — ' . $vm->name)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('client.vms.show', $vm) }}" class="text-sm text-gray-400 hover:text-gray-600">← {{ $vm->name }}</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Réinstaller la VM</h1>
</div>

<div class="max-w-xl space-y-6">

    {{-- Avertissement --}}
    <div class="bg-red-50 border border-red-200 rounded-xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-red-800 text-sm">Toutes les données seront effacées</p>
                <p class="text-red-700 text-sm mt-1">
                    La réinstallation supprime définitivement le disque de la VM et le remplace par un disque vierge.
                    Cette action est <strong>irréversible</strong>.
                </p>
            </div>
        </div>
    </div>

    @if($templates->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center text-gray-500 text-sm">
        Aucun template OS disponible. Contactez le support.
    </div>
    @else
    <form method="POST" action="{{ route('client.vms.doReinstall', $vm) }}"
          onsubmit="return confirm('Confirmer la réinstallation ? Toutes les données seront perdues.')">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

            {{-- Sélection OS --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">Système d'exploitation</label>
                <div class="space-y-2">
                    @foreach($templates as $tpl)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="os_template_id" value="{{ $tpl->id }}"
                               class="text-indigo-600"
                               {{ $loop->first ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $tpl->name }}</div>
                            @if($tpl->description)
                            <div class="text-xs text-gray-500">{{ $tpl->description }}</div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('os_template_id')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Bouton --}}
            <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Réinstaller la VM
            </button>
        </div>
    </form>
    @endif

</div>
@endsection
