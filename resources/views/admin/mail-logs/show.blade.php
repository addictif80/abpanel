@extends('layouts.app')
@section('title', 'Détail du mail')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <a href="{{ route('admin.mail-logs.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Journaux des mails</a>
        <h1 class="text-xl font-bold text-gray-900 mt-1">{{ $mailLog->subject }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                {{ $mailLog->status === 'sent' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $mailLog->status === 'sent' ? 'Envoyé' : 'Échec' }}
            </span>
            @if($mailLog->template_key)
            <code class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">{{ $mailLog->template_key }}</code>
            @endif
            <span class="text-xs text-gray-400">{{ $mailLog->created_at->format('d/m/Y à H:i:s') }}</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    {{-- Email preview --}}
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">Aperçu de l'email</span>
                <a href="{{ route('admin.mail-logs.show', $mailLog) }}?raw=1"
                   target="_blank"
                   class="text-xs text-indigo-600 hover:underline">Voir le HTML brut</a>
            </div>
            <iframe
                srcdoc="{{ htmlspecialchars($mailLog->html_content) }}"
                class="w-full border-0"
                style="min-height: 500px;"
                onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'">
            </iframe>
        </div>

        @if($mailLog->status === 'failed' && $mailLog->error)
        <div class="mt-4 bg-red-50 border border-red-100 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-red-700 mb-1">Erreur d'envoi</h3>
            <pre class="text-xs text-red-600 whitespace-pre-wrap">{{ $mailLog->error }}</pre>
        </div>
        @endif
    </div>

    {{-- Infos --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Informations</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-400 uppercase">Destinataire</dt>
                    <dd class="text-gray-800 mt-0.5 break-all">{{ $mailLog->to }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase">Sujet</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $mailLog->subject }}</dd>
                </div>
                @if($mailLog->template_key)
                <div>
                    <dt class="text-xs text-gray-400 uppercase">Template</dt>
                    <dd class="mt-0.5"><code class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">{{ $mailLog->template_key }}</code></dd>
                </div>
                @endif
                <div>
                    <dt class="text-xs text-gray-400 uppercase">Statut</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $mailLog->status === 'sent' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $mailLog->status === 'sent' ? 'Envoyé' : 'Échec' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase">Date</dt>
                    <dd class="text-gray-600 text-xs mt-0.5">{{ $mailLog->created_at->format('d/m/Y H:i:s') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

