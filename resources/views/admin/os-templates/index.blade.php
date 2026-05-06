@extends('layouts.app')
@section('title', 'Templates OS')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Templates OS</h1>
        <p class="text-gray-500 text-sm mt-0.5">ISOs disponibles pour l'installation et la réinstallation des VMs.</p>
    </div>
</div>

@if(session('success'))
<div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- Add form --}}
    <div class="xl:col-span-2"
         x-data="osForm({{ json_encode($nodes) }})"
         x-init="init()">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4 sticky top-6">
            <h2 class="font-semibold text-gray-800">Ajouter un ISO</h2>

            @if($errors->any())
            <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <form method="POST" action="{{ route('admin.os-templates.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom affiché <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Debian 12 Bookworm"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Debian 12.x — amd64"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL de l'ISO <span class="text-red-500">*</span></label>
                    <input type="url" name="url" value="{{ old('url') }}" required
                        placeholder="https://cdimage.debian.org/.../debian-12.iso"
                        @input="guessFilename($event.target.value)"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono text-xs">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du fichier <span class="text-red-500">*</span></label>
                    <input type="text" name="filename" value="{{ old('filename') }}" required
                        placeholder="debian-12-amd64.iso"
                        x-model="filename"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    <p class="text-xs text-gray-400 mt-0.5">Doit se terminer par .iso</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nœud Proxmox <span class="text-red-500">*</span></label>
                    <select name="proxmox_node" required x-model="selectedNode" @change="loadStorages()"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Choisir un nœud —</option>
                        @foreach($nodes as $node)
                        <option value="{{ $node }}" {{ old('proxmox_node') === $node ? 'selected' : '' }}>{{ $node }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stockage <span class="text-red-500">*</span></label>
                    <div x-show="loadingStorages" class="text-xs text-gray-400 py-2">Chargement…</div>
                    <select name="proxmox_storage" required x-show="!loadingStorages && storages.length > 0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Choisir un stockage ISO —</option>
                        <template x-for="s in storages" :key="s.id">
                            <option :value="s.id" x-text="s.name"
                                :selected="'{{ old('proxmox_storage') }}' === s.id"></option>
                        </template>
                    </select>
                    <p x-show="!loadingStorages && selectedNode && storages.length === 0"
                       class="text-xs text-amber-600 mt-1">
                        Aucun stockage ISO trouvé sur ce nœud.
                    </p>
                    <p x-show="storageError" class="text-xs text-red-500 mt-1" x-text="storageError"></p>
                </div>

                <button type="submit"
                    class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Lancer le téléchargement
                </button>
            </form>
        </div>
    </div>

    {{-- Templates list --}}
    <div class="xl:col-span-3">
        <div class="space-y-3" id="templates-list">
            @forelse($templates as $tpl)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
                 id="tpl-{{ $tpl->id }}"
                 @if($tpl->isDownloading()) x-data="poller({{ $tpl->id }}, '{{ $tpl->status }}')" x-init="start()" @endif>

                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900">{{ $tpl->name }}</span>

                            {{-- Status badge --}}
                            @if($tpl->status === 'ready')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Disponible
                            </span>
                            @elseif($tpl->status === 'downloading')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700"
                                  @if($tpl->isDownloading()) :class="currentStatus === 'ready' ? 'bg-green-100 text-green-700' : (currentStatus === 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')"
                                  x-text="statusLabel" @endif>
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="statusLabel">Téléchargement…</span>
                            </span>
                            @elseif($tpl->status === 'pending')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                En attente
                            </span>
                            @elseif($tpl->status === 'error')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                Erreur
                            </span>
                            @endif

                            {{-- Active toggle --}}
                            @if($tpl->status === 'ready')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tpl->is_active ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-100 text-gray-400' }}">
                                {{ $tpl->is_active ? 'Visible' : 'Masqué' }}
                            </span>
                            @endif
                        </div>

                        @if($tpl->description)
                        <div class="text-xs text-gray-500 mt-0.5">{{ $tpl->description }}</div>
                        @endif

                        <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-2 text-xs text-gray-400">
                            <span class="font-mono">{{ $tpl->proxmox_node }} / {{ $tpl->proxmox_storage }}</span>
                            <span class="font-mono">{{ $tpl->filename }}</span>
                            @if($tpl->size_bytes)<span>{{ $tpl->formattedSize() }}</span>@endif
                        </div>

                        @if($tpl->proxmox_volume)
                        <div class="mt-1 font-mono text-xs text-gray-400">{{ $tpl->proxmox_volume }}</div>
                        @endif

                        @if($tpl->status === 'error' && $tpl->error_message)
                        <div class="mt-2 px-3 py-2 bg-red-50 border border-red-100 rounded text-xs text-red-600 font-mono"
                             @if($tpl->isDownloading()) x-show="errorMsg" x-text="errorMsg" @endif>
                            {{ $tpl->error_message }}
                        </div>
                        @elseif($tpl->isDownloading())
                        <div x-show="errorMsg" class="mt-2 px-3 py-2 bg-red-50 border border-red-100 rounded text-xs text-red-600 font-mono" x-text="errorMsg"></div>
                        @endif
                    </div>

                    <div class="flex flex-col items-end gap-2 shrink-0">
                        @if($tpl->status === 'ready')
                        <form method="POST" action="{{ route('admin.os-templates.toggle', $tpl) }}">
                            @csrf
                            <button type="submit"
                                class="text-xs px-3 py-1.5 rounded-lg border {{ $tpl->is_active ? 'border-gray-200 text-gray-500 hover:bg-gray-50' : 'border-indigo-200 text-indigo-600 hover:bg-indigo-50' }} transition">
                                {{ $tpl->is_active ? 'Masquer' : 'Activer' }}
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.os-templates.destroy', $tpl) }}"
                              onsubmit="return confirm('Supprimer cet ISO de Proxmox et du panel ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-100 text-red-500 hover:bg-red-50 transition">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Progress bar for active downloads --}}
                @if($tpl->isDownloading())
                <div class="mt-3" x-show="currentStatus === 'downloading' || currentStatus === 'pending'">
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-500 h-1.5 rounded-full animate-pulse" style="width: 60%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Téléchargement en cours sur Proxmox…</p>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
                Aucun template OS. Ajoutez votre premier ISO ci-contre.
            </div>
            @endforelse
        </div>
    </div>

</div>

@push('scripts')
<script>
function osForm(nodes) {
    return {
        nodes,
        selectedNode: '{{ old('proxmox_node', '') }}',
        storages: [],
        loadingStorages: false,
        storageError: '',
        filename: '{{ old('filename', '') }}',

        init() {
            if (this.selectedNode) this.loadStorages();
        },

        guessFilename(url) {
            try {
                const parts = new URL(url).pathname.split('/');
                const last = parts[parts.length - 1];
                if (last.toLowerCase().endsWith('.iso')) this.filename = last;
            } catch {}
        },

        async loadStorages() {
            if (!this.selectedNode) return;
            this.loadingStorages = true;
            this.storages = [];
            this.storageError = '';
            try {
                const res = await fetch('{{ route('admin.os-templates.storages') }}?node=' + encodeURIComponent(this.selectedNode), {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.error) {
                    this.storageError = data.error;
                } else {
                    this.storages = data;
                }
            } catch (e) {
                this.storageError = 'Erreur de communication.';
            } finally {
                this.loadingStorages = false;
            }
        }
    };
}

function poller(id, initialStatus) {
    const statusLabels = {
        pending:     'En attente…',
        downloading: 'Téléchargement…',
        ready:       'Disponible',
        error:       'Erreur',
    };

    return {
        currentStatus: initialStatus,
        statusLabel: statusLabels[initialStatus] ?? initialStatus,
        errorMsg: '',
        timer: null,

        start() {
            if (this.currentStatus === 'ready' || this.currentStatus === 'error') return;
            this.timer = setInterval(() => this.poll(), 4000);
        },

        async poll() {
            try {
                const res = await fetch(`/admin/os-templates/${id}/status`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                this.currentStatus = data.status;
                this.statusLabel   = statusLabels[data.status] ?? data.status;
                this.errorMsg      = data.error_message ?? '';

                if (data.status === 'ready' || data.status === 'error') {
                    clearInterval(this.timer);
                    // Reload page to show final state cleanly
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch {}
        }
    };
}
</script>
@endpush
@endsection
