@extends('layouts.app')
@section('title', 'Modifier — ' . $template->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('admin.mail-templates.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Templates</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $template->name }}</h1>
    </div>
</div>

<div x-data="mailEditor()" class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Éditeur --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('admin.mail-templates.update', $template) }}" id="templateForm">
            @csrf @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                    <input type="text" name="subject" x-model="subject" value="{{ old('subject', $template->subject) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Contenu HTML</label>
                        <div class="flex gap-2 text-xs">
                            @foreach($template->variables ?? [] as $var)
                            <button type="button" @click="insertVar('{{ $var }}')"
                                class="px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 font-mono">
                                {{ '{{' . $var . '}}' }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <textarea name="html_content" id="htmlEditor" x-model="html" rows="20"
                        @input="updatePreview()"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('html_content', $template->html_content) }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600">
                    <label for="is_active" class="text-sm text-gray-700">Template actif</label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Enregistrer
                </button>
                <div x-data="{ email: '{{ auth()->user()->email }}', loading: false, msg: '' }">
                    <div class="flex items-center gap-2">
                        <input type="email" x-model="email" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none w-48">
                        <button type="button" @click="sendTest()" :disabled="loading"
                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                            <span x-text="loading ? 'Envoi...' : 'Tester'"></span>
                        </button>
                        <span x-show="msg" :class="ok ? 'text-green-600' : 'text-red-600'" class="text-sm" x-text="msg"></span>
                    </div>
                    <script>
                        function sendTest() {
                            // handled inline via fetch below
                        }
                    </script>
                </div>
            </div>
        </form>

        {{-- Test email inline --}}
        <div x-data="{ email: '{{ auth()->user()->email }}', loading: false, msg: '', ok: false }" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Envoyer un email de test</h3>
            <div class="flex gap-2">
                <input type="email" x-model="email" placeholder="Email de destination"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <button type="button" :disabled="loading" @click="
                    loading = true; msg = '';
                    fetch('{{ route('admin.mail-templates.test', $template) }}', {
                        method:'POST',
                        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                        body: JSON.stringify({email})
                    }).then(r=>r.json()).then(d=>{ ok=d.success; msg=d.message; loading=false; })"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                    <span x-text="loading ? 'Envoi...' : 'Envoyer'"></span>
                </button>
            </div>
            <p x-show="msg" :class="ok ? 'text-green-600' : 'text-red-600'" class="text-xs mt-2" x-text="msg"></p>
        </div>
    </div>

    {{-- Preview live --}}
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
function mailEditor() {
    return {
        html: document.getElementById('htmlEditor')?.value ?? '',
        subject: '{{ addslashes($template->subject) }}',
        init() {
            this.updatePreview();
        },
        updatePreview() {
            const frame = document.getElementById('previewFrame');
            const doc = frame.contentDocument || frame.contentWindow.document;
            doc.open();
            doc.write(this.html);
            doc.close();
        },
        insertVar(varName) {
            const ta = document.getElementById('htmlEditor');
            const start = ta.selectionStart;
            const val = ta.value;
            ta.value = val.slice(0, start) + '{{' + varName + '}}' + val.slice(ta.selectionEnd);
            this.html = ta.value;
            this.updatePreview();
            ta.focus();
            ta.selectionStart = ta.selectionEnd = start + varName.length + 4;
        }
    }
}
</script>
@endpush
