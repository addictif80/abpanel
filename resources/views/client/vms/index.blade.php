@extends('layouts.app')
@section('title', 'Mes VMs')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mes machines virtuelles</h1>
    <p class="text-gray-500 text-sm mt-1">{{ $vms->count() }} VM(s)</p>
</div>

@if($vms->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
    </svg>
    <p class="text-gray-400 text-sm">Vous n'avez pas encore de machine virtuelle.</p>
    <p class="text-gray-400 text-sm mt-1">Contactez le support pour en commander une.</p>
    <a href="{{ route('client.tickets.create') }}" class="mt-4 inline-block px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Contacter le support
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($vms as $vm)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">{{ $vm->name }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $vm->subdomain ?: $vm->custom_domain ?: 'Pas de domaine' }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' :
                   ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $vm->status === 'running' ? 'bg-green-500 animate-pulse' : ($vm->status === 'hibernated' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                {{ ucfirst($vm->status) }}
            </span>
        </div>

        <div class="grid grid-cols-3 gap-2 mb-4 text-center">
            <div class="bg-gray-50 rounded-lg p-2">
                <div class="text-sm font-bold text-gray-800">{{ $vm->cores }}</div>
                <div class="text-xs text-gray-400">vCPU</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-2">
                <div class="text-sm font-bold text-gray-800">{{ $vm->memory_mb >= 1024 ? round($vm->memory_mb / 1024, 1) . 'GB' : $vm->memory_mb . 'MB' }}</div>
                <div class="text-xs text-gray-400">RAM</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-2">
                <div class="text-sm font-bold text-gray-800">{{ $vm->disk_gb }}GB</div>
                <div class="text-xs text-gray-400">Disque</div>
            </div>
        </div>

        <a href="{{ route('client.vms.show', $vm) }}"
           class="block w-full text-center py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Gérer cette VM
        </a>
    </div>
    @endforeach
</div>
@endif
@endsection
