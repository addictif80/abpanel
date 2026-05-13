@extends('layouts.app')
@section('title', 'Nouveau template mail')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.mail-templates.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Templates</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouveau template mail</h1>
</div>

<div x-data="{ html: '', subject: '' }" class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    <form method="POST" action="{{ route('admin.mail-templates.store') }}" class="space-y-4">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé <span class="text-gray-400 font-normal">(identifiant unique)</span></label>
                    <input type="text" name="key" value="{{ old('key') }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('key') border-red-400 @enderror"
                        placeholder="hosting_provisioned">
                    @error('key')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('name') border-red-400 @enderror"
                        placeholder="Hébergement provisionné">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                <input type="text" name="subject" x-model="subject" value="{{ old('subject') }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('subject') border-red-400 @enderror"
                    placeholder="Votre hébergement {{domain}} est prêt">
                @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Variables disponibles
                    <span class="text-gray-400 font-normal">(séparées par des virgules)</span>
                </label>
                <input type="text" name="variables" value="{{ old('variables') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    placeholder="first_name, domain, username, password, panel_url">
                <p class="text-xs text-gray-400 mt-1">Ces variables seront disponibles comme boutons d'insertion lors de l'édition.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenu HTML</label>
                <textarea name="html_content" id="htmlEditor" x-model="html" rows="20" required
                    @input="updatePreview()"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('html_content') border-red-400 @enderror"
                    placeholder="Collez votre HTML ici...">{{ old('html_content') }}</textarea>
                @error('html_content')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Créer le template
            </button>
            <a href="{{ route('admin.mail-templates.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Annuler</a>
        </div>
    </form>

    <div class="sticky top-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <span class="text-sm font-semibold text-gray-700">Prévisualisation</span>
                <span class="text-xs text-gray-400 font-mono" x-text="'Sujet : ' + subject"></span>
            </div>
            <iframe id="previewFrame" class="w-full" style="height: 600px; border: none;"></iframe>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updatePreview() {
    const frame = document.getElementById('previewFrame');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(document.getElementById('htmlEditor').value); doc.close();
}
document.getElementById('htmlEditor').addEventListener('input', updatePreview);
</script>
@endpush
