@extends('layouts.app')
@section('title', $list->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $list->name }}</h1>
        <p class="text-gray-500 text-sm">{{ $subscribers->total() }} abonné(s)</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 text-sm mb-3">Ajouter un abonné</h2>
        <div x-data="{
            mode: 'manual',
            userId: '',
            email: '',
            firstName: '',
            selectUser(id, email, first) {
                this.userId = id;
                this.email = email;
                this.firstName = first;
            }
        }">
            {{-- Mode toggle --}}
            <div class="flex rounded-lg border border-gray-200 overflow-hidden mb-4 text-xs font-semibold">
                <button type="button" @click="mode = 'manual'; userId = ''; email = ''; firstName = ''"
                    :class="mode === 'manual' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="flex-1 py-1.5 transition">Saisie manuelle</button>
                <button type="button" @click="mode = 'client'"
                    :class="mode === 'client' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="flex-1 py-1.5 transition">Client existant</button>
            </div>

            <form method="POST" action="{{ route('admin.newsletter.lists.subscribers.store', $list) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="user_id" :value="userId">

                {{-- Client selector --}}
                <div x-show="mode === 'client'" x-cloak>
                    <select @change="
                        const opt = $event.target.selectedOptions[0];
                        if (opt.value) {
                            selectUser(opt.value, opt.dataset.email, opt.dataset.first);
                        } else {
                            userId = ''; email = ''; firstName = '';
                        }
                    " class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un client —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}"
                            data-email="{{ $client->email }}"
                            data-first="{{ $client->first_name }}">
                            {{ $client->full_name }} ({{ $client->email }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Email field (manual mode or read-only preview in client mode) --}}
                <div>
                    <input type="email" name="email" x-model="email"
                        :readonly="mode === 'client' && userId !== ''"
                        :required="mode === 'manual'"
                        placeholder="Email"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        :class="mode === 'client' && userId ? 'bg-gray-50 text-gray-500' : ''">
                </div>

                {{-- First name (always editable) --}}
                <input type="text" name="first_name" x-model="firstName" placeholder="Prénom (optionnel)"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Ajouter
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prénom</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Inscrit le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($subscribers as $sub)
                <tr>
                    <td class="px-5 py-3 text-gray-800">{{ $sub->email }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $sub->first_name ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sub->status === 'subscribed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $sub->status === 'subscribed' ? 'Abonné' : 'Désabonné' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sub->subscribed_at?->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Aucun abonné</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($subscribers->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">{{ $subscribers->links() }}</div>
        @endif
    </div>
</div>
@endsection
