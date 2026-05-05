@extends('layouts.app')
@section('title', 'Nouvelle campagne')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.newsletter.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Newsletter</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouvelle campagne</h1>
</div>

<div x-data="{ html: '', subject: '' }" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom interne *</label>
                <input type="text" name="name" required placeholder="ex: Promo mai 2025"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet *</label>
                <input type="text" name="subject" x-model="subject" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Contenu HTML *</label>
                    <div class="flex gap-2 text-xs text-gray-400">
                        Variables : <code class="bg-gray-100 px-1 rounded">{{first_name}}</code>
                        <code class="bg-gray-100 px-1 rounded">{{email}}</code>
                        <code class="bg-gray-100 px-1 rounded">{{unsubscribe_url}}</code>
                    </div>
                </div>
                <textarea name="html_content" id="campaignHtml" x-model="html" rows="18" required
                    @input="updatePreview()"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    placeholder="Collez votre HTML ici..."></textarea>
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
                <span class="text-xs text-gray-400 ml-2" x-text="'Sujet : ' + subject"></span>
            </div>
            <iframe id="campaignPreview" class="w-full" style="height:560px;border:none;"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updatePreview() {
    const frame = document.getElementById('campaignPreview');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(document.getElementById('campaignHtml').value); doc.close();
}
document.getElementById('campaignHtml').addEventListener('input', updatePreview);
</script>
@endpush
@endsection
