@extends('layouts.app')
@section('title', 'Nouvelle campagne')
@section('sidebar')<x-admin-sidebar />@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>.ql-editor { min-height: 260px; font-size: 14px; }</style>
@endpush

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouvelle campagne</h1>
    <p class="text-gray-500 text-sm mt-0.5">Le design (en-tête, couleurs, pied de page) est le même que pour vos emails automatiques — il n'y a que le texte à saisir.</p>
</div>

<div x-data="campaignForm('', '')" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('admin.newsletter.campaigns.store') }}" class="space-y-4">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Liste *</label>
                <select name="list_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    @foreach($lists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->active_subscribers_count }} actifs)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre interne *</label>
                <input type="text" name="name" required placeholder="ex: Promo mai 2025"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Pour vous y retrouver dans la liste des campagnes — n'apparaît pas dans l'email.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet *</label>
                <input type="text" name="subject" x-model="subject" @input="preview()" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Utilisé comme objet de l'email et comme titre affiché dans le message.</p>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Message *</label>
                    <div class="flex gap-2 text-xs text-gray-400">
                        Variables : <code class="bg-gray-100 px-1 rounded">@{{first_name}}</code>
                        <code class="bg-gray-100 px-1 rounded">@{{email}}</code>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-300 overflow-hidden">
                    <div x-ref="editor"></div>
                </div>
                <textarea name="message" x-model="message" class="hidden"></textarea>
                <p class="text-xs text-gray-400 mt-1">Pour une image, utilisez le bouton image de la barre d'outils et collez l'URL d'une image déjà hébergée en ligne (plus fiable qu'un fichier importé pour l'affichage dans les emails). Le lien de désinscription est ajouté automatiquement en pied de page.</p>
            </div>
        </div>
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer la campagne
        </button>
    </form>

    <div class="sticky top-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <span class="text-sm font-semibold text-gray-700">Prévisualisation</span>
                <span class="text-xs text-gray-400 ml-2" x-text="'Sujet : ' + (subject || '(à saisir)')"></span>
            </div>
            <iframe id="campaignPreview" class="w-full" style="height:560px;border:none;"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
function campaignForm(initialSubject, initialMessage) {
    return {
        subject: initialSubject,
        message: initialMessage,
        quill: null,
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
        init() {
            this.quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: 'Bonjour @{{first_name}},\n\nVotre texte ici...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image'],
                        ['clean'],
                    ],
                },
            });
            if (this.message) {
                this.quill.clipboard.dangerouslyPasteHTML(this.message);
            }
            this.quill.on('text-change', () => {
                this.message = this.quill.root.innerHTML;
                this.preview();
            });
            this.renderPreview();
        }
    };
}
</script>
@endpush
@endsection
