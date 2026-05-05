@extends('layouts.app')
@section('title', 'Mon profil')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mon profil</h1>
</div>

<div x-data="{ tab: '{{ session('tab', 'info') }}' }" class="max-w-2xl">

    {{-- Tabs --}}
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-6">
        <button @click="tab = 'info'" :class="tab === 'info' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 py-2 text-sm font-medium rounded-lg transition">Informations</button>
        <button @click="tab = 'password'" :class="tab === 'password' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 py-2 text-sm font-medium rounded-lg transition">Mot de passe</button>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-3 text-green-800 text-sm border border-green-200">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
    <div class="mb-4 rounded-lg bg-amber-50 p-3 text-amber-800 text-sm border border-amber-200">{{ session('warning') }}</div>
    @endif

    {{-- Infos --}}
    <div x-show="tab === 'info'">
        <form method="POST" action="{{ route('client.profile.update') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" value="{{ $user->email }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Contactez le support pour changer votre email.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Société</label>
                    <input type="text" name="company" value="{{ old('company', $user->company) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" name="address" value="{{ old('address', $user->address) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city', $user->city) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                    <input type="text" name="zip" value="{{ old('zip', $user->zip) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div x-show="tab === 'password'">
        <form method="POST" action="{{ route('client.profile.password') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                <input type="password" name="current_password" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('current_password') border-red-400 @enderror">
                @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('password') border-red-400 @enderror">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            @if(auth()->user()->cyberpanel_username)
            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-700">
                Votre mot de passe CyberPanel sera synchronisé automatiquement.
            </div>
            @endif

            <div class="pt-4 border-t border-gray-100">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
