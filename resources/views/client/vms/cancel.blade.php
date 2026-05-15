@extends('layouts.app')
@section('title', 'Résilier ' . $vm->name)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="max-w-lg mx-auto">

    <a href="{{ route('client.vms.show', $vm) }}" class="text-sm text-gray-400 hover:text-gray-600">← Retour au VPS</a>

    <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-5 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <h2 class="font-bold text-red-800 text-base">Résiliation définitive — {{ $vm->name }}</h2>
                <p class="text-sm text-red-700 mt-1">
                    Cette action est <strong>irréversible</strong>. Votre serveur et <strong>toutes les données qu'il contient</strong>
                    seront supprimés définitivement sur Proxmox. Aucune sauvegarde ne sera conservée.
                </p>
                <ul class="mt-3 space-y-1 text-sm text-red-700 list-disc list-inside">
                    <li>Tous vos fichiers et données seront perdus</li>
                    <li>Le VPS ne pourra pas être restauré</li>
                    <li>La facturation s'arrêtera à la date de résiliation</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Confirmation par email</h3>
        <p class="text-sm text-gray-500 mb-6">
            Un code à 6 chiffres vient d'être envoyé à <strong>{{ auth()->user()->email }}</strong>.
            Saisissez-le ci-dessous pour confirmer la résiliation. Le code expire dans <strong>15 minutes</strong>.
        </p>

        @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('client.vms.cancelConfirm', $vm) }}">
            @csrf
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Code de confirmation</label>
                <input type="text" name="code" value="{{ old('code') }}"
                    maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
                    placeholder="000000"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-2xl font-mono font-bold tracking-widest focus:ring-2 focus:ring-red-500 focus:outline-none @error('code') border-red-500 @enderror"
                    required autofocus>
                <p class="text-xs text-gray-400 mt-1 text-center">Vérifiez votre boîte mail (et les spams).</p>
            </div>

            <button type="submit"
                onclick="return confirm('Dernière confirmation : supprimer définitivement le VPS « {{ addslashes($vm->name) }} » et toutes ses données ?')"
                class="w-full py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition text-sm">
                Confirmer la résiliation définitive
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('client.vms.cancel', $vm) }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Renvoyer un nouveau code
            </a>
            <span class="text-gray-300 mx-2">·</span>
            <a href="{{ route('client.vms.show', $vm) }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Annuler
            </a>
        </div>
    </div>

</div>
@endsection
