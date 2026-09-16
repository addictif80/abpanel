@extends('layouts.app')
@section('title', 'Annuaire LDAP')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Annuaire LDAP</h1>
        <p class="text-gray-500 text-sm mt-0.5">Comptes et groupes lus en direct sur le serveur LDAP configuré.</p>
    </div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.ldap.import.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">📥 Importer un compte</a>
        <a href="{{ route('admin.settings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">⚙️ Configuration LDAP</a>
    </div>
</div>

@if($error)
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    Impossible de contacter le serveur LDAP : {{ $error }}
</div>
@else

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Users --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Utilisateurs</h2>
            <span class="text-xs text-gray-400">{{ count($users) }} compte(s)</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 text-xs uppercase bg-gray-50">
                    <th class="px-5 py-2">uid</th>
                    <th class="px-5 py-2">cn</th>
                    <th class="px-5 py-2">mail</th>
                    <th class="px-5 py-2">uidNumber</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr>
                    <td class="px-5 py-2 font-mono text-gray-800">{{ $user['uid'] }}</td>
                    <td class="px-5 py-2 text-gray-600">{{ $user['cn'] }}</td>
                    <td class="px-5 py-2 text-gray-600">{{ $user['mail'] }}</td>
                    <td class="px-5 py-2 text-gray-400 font-mono">{{ $user['uidNumber'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-6 text-center text-gray-400">Aucun compte trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Groups --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Groupes</h2>
            <span class="text-xs text-gray-400">{{ count($groups) }} groupe(s)</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 text-xs uppercase bg-gray-50">
                    <th class="px-5 py-2">cn</th>
                    <th class="px-5 py-2">gidNumber</th>
                    <th class="px-5 py-2">Membres</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($groups as $group)
                <tr>
                    <td class="px-5 py-2 font-mono text-gray-800">{{ $group['cn'] }}</td>
                    <td class="px-5 py-2 text-gray-400 font-mono">{{ $group['gidNumber'] }}</td>
                    <td class="px-5 py-2 text-gray-600">
                        @if(count($group['members']))
                            {{ implode(', ', $group['members']) }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">Aucun groupe trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endif
@endsection
