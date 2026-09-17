@extends('layouts.app')
@section('title', 'Modifier — ' . $campaign->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $campaign->name }}</h1>
    <p class="text-gray-500 text-sm mt-0.5">Le design (en-tête, couleurs, pied de page) est le même que pour vos emails automatiques — il n'y a que le texte à modifier.</p>
</div>

<div x-data="campaignForm({{ json_encode(old('subject', $campaign->subject)) }}, {{ json_encode(old('message', $campaign->message)) }})" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('admin.newsletter.campaigns.update', $campaign) }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre interne</label>
                <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                <input type="text" name="subject" x-model="subject" @input="preview()" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Message</label>
                    <div class="flex gap-2 text-xs text-gray-400">
                        Variables : <code class="bg-gray-100 px-1 rounded">@{{first_name}}</code>
                        <code class="bg-gray-100 px-1 rounded">@{{email}}</code>
                    </div>
                </div>
                <textarea name="message" x-model="message" rows="14" required
                    @input="preview()"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                <p class="text-xs text-gray-400 mt-1">Texte simple — les paragraphes (séparés par une ligne vide) sont automatiquement mis en forme. Le lien de désinscription est ajouté automatiquement.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Enregistrer
            </button>
        </div>
    </form>

    <div class="sticky top-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <span class="text-sm font-semibold text-gray-700">Prévisualisation</span>
            </div>
            <iframe id="campaignPreview" class="w-full" style="height:560px;border:none;"></iframe>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}"
    onsubmit="return confirm('Supprimer cette campagne ?')" class="mt-3">
    @csrf @method('DELETE')
    <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
        Supprimer
    </button>
</form>

@push('scripts')
<script>
function campaignForm(initialSubject, initialMessage) {
    return {
        subject: initialSubject,
        message: initialMessage,
        debounceTimer: null,
        preview() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.renderPreview(), 300);
        },
        async renderPreview() {
            try {
                const res = await fetch('{{ route('admin.newsletter.campaigns.preview') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ subject: this.subject, message: this.message }),
                });
                const data = await res.json();
                const frame = document.getElementById('campaignPreview');
                const doc = frame.contentDocument || frame.contentWindow.document;
                doc.open(); doc.write(data.html || ''); doc.close();
            } catch (e) {}
        },
        init() { this.renderPreview(); }
    };
}
</script>
@endpush
@endsection
