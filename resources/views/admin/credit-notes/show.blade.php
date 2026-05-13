@extends('layouts.app')
@section('title', $creditNote->number)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('admin.credit-notes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Avoirs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $creditNote->number }}</h1>
    </div>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $creditNote->statusColor() }}">
        {{ $creditNote->statusLabel() }}
    </span>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Client</p>
                    <div class="font-medium text-gray-800">{{ $creditNote->user->full_name }}</div>
                    <div class="text-gray-400">{{ $creditNote->user->email }}</div>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Facture concernée</p>
                    <a href="{{ route('admin.invoices.show', $creditNote->invoice) }}" class="font-mono text-indigo-600 hover:underline">
                        {{ $creditNote->invoice->number }}
                    </a>
                    <div class="text-gray-400">{{ number_format($creditNote->invoice->total, 2) }} {{ $creditNote->invoice->currency }}</div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <div class="flex justify-between font-bold text-xl text-gray-900">
                    <span>Montant de l'avoir</span>
                    <span>{{ number_format($creditNote->amount, 2) }} {{ $creditNote->currency }}</span>
                </div>
            </div>

            @if($creditNote->reason)
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Motif</p>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $creditNote->reason }}</p>
            </div>
            @endif

            @if($creditNote->issued_at)
            <p class="text-xs text-gray-400">Émis le {{ $creditNote->issued_at->format('d/m/Y à H:i') }}</p>
            @endif
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Actions</h3>
            @if($creditNote->status === 'draft')
            <form method="POST" action="{{ route('admin.credit-notes.issue', $creditNote) }}">
                @csrf
                <button class="w-full px-3 py-2 text-sm text-blue-700 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition text-left">
                    Émettre l'avoir
                </button>
            </form>
            @endif
            @if($creditNote->status === 'issued')
            <form method="POST" action="{{ route('admin.credit-notes.apply', $creditNote) }}">
                @csrf
                <button class="w-full px-3 py-2 text-sm text-green-700 bg-green-50 border border-green-100 rounded-lg hover:bg-green-100 transition text-left">
                    Marquer comme appliqué
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
