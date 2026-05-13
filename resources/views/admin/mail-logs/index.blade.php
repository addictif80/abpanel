@extends('layouts.app')
@section('title', 'Journaux des mails')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Journaux des mails</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $logs->total() }} email(s) enregistré(s)</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="divide-y divide-gray-50">
        @forelse($logs as $log)
        <div class="flex items-center justify-between px-5 py-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $log->status === 'sent' ? 'Envoyé' : 'Échec' }}
                    </span>
                    @if($log->template_key)
                    <code class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">{{ $log->template_key }}</code>
                    @endif
                </div>
                <div class="font-medium text-sm text-gray-800 truncate">{{ $log->subject }}</div>
                <div class="text-xs text-gray-400 mt-0.5">→ {{ $log->to }} · {{ $log->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="ml-4 shrink-0">
                <a href="{{ route('admin.mail-logs.show', $log) }}"
                   class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">
                    Détails
                </a>
            </div>
        </div>
        @empty
        <div class="px-5 py-12 text-center text-gray-400">Aucun email enregistré</div>
        @endforelse
    </div>
    @if($logs->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
