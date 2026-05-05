@extends('layouts.app')
@section('title', 'Modifier — ' . $campaign->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $campaign->name }}</h1>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('admin.newsletter.campaigns.update', $campaign) }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom interne</label>
                <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                <input type="text" name="subject" id="editSubject" value="{{ old('subject', $campaign->subject) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenu HTML</label>
                <textarea name="html_content" id="editHtml" rows="18" required
                    @input="updatePreview()"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('html_content', $campaign->html_content) }}</textarea>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Enregistrer
            </button>
            <form method="POST" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}"
                onsubmit="return confirm('Supprimer cette campagne ?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
                    Supprimer
                </button>
            </form>
        </div>
    </form>

    <div class="sticky top-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <span class="text-sm font-semibold text-gray-700">Prévisualisation</span>
            </div>
            <iframe id="editPreview" class="w-full" style="height:560px;border:none;"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateEditPreview() {
    const frame = document.getElementById('editPreview');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(document.getElementById('editHtml').value); doc.close();
}
document.getElementById('editHtml').addEventListener('input', updateEditPreview);
updateEditPreview();
</script>
@endpush
@endsection
