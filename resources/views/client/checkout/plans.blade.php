@extends('layouts.app')
@section('title', 'Nos offres')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Nos offres</h1>
    <p class="text-gray-500 text-sm mt-1">Choisissez le plan adapté à vos besoins.</p>
</div>

@foreach($plans as $type => $typePlans)
<div class="mb-10">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $type === 'vm' ? 'Serveurs virtuels (VPS)' : 'Hébergements web' }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($typePlans as $plan)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col">
            <div class="flex-1">
                <h3 class="font-bold text-gray-900 text-lg">{{ $plan->name }}</h3>
                @if($plan->description)
                <p class="text-gray-500 text-sm mt-1">{{ $plan->description }}</p>
                @endif

                <div class="mt-4 mb-5">
                    <span class="text-3xl font-bold text-gray-900">{{ number_format($plan->price, 2) }}€</span>
                    <span class="text-gray-400 text-sm">/{{ $plan->billing_period === 'yearly' ? 'an' : 'mois' }}</span>
                </div>

                @if($plan->cores || $plan->memory_mb || $plan->disk_gb)
                <div class="bg-gray-50 rounded-lg p-3 mb-4 grid grid-cols-3 gap-2 text-center text-xs">
                    @if($plan->cores)
                    <div>
                        <div class="font-bold text-gray-800">{{ $plan->cores }}</div>
                        <div class="text-gray-500">vCPU</div>
                    </div>
                    @endif
                    @if($plan->memory_mb)
                    <div>
                        <div class="font-bold text-gray-800">{{ $plan->memory_mb >= 1024 ? round($plan->memory_mb/1024, 0) . ' GB' : $plan->memory_mb . ' MB' }}</div>
                        <div class="text-gray-500">RAM</div>
                    </div>
                    @endif
                    @if($plan->disk_gb)
                    <div>
                        <div class="font-bold text-gray-800">{{ $plan->disk_gb }} GB</div>
                        <div class="text-gray-500">Stockage</div>
                    </div>
                    @endif
                </div>
                @endif

                @if(!empty($plan->features))
                <ul class="space-y-1.5 text-sm text-gray-600">
                    @foreach($plan->features as $feature)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <div class="mt-6">
                @if($plan->price > 0)
                <a href="{{ route('client.checkout.checkout', $plan) }}"
                   class="block w-full text-center py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Commander
                </a>
                @else
                <a href="{{ route('client.checkout.checkout', $plan) }}"
                   class="block w-full text-center py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                    Démarrer gratuitement
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

@if($plans->isEmpty())
<div class="text-center py-16 text-gray-400">Aucune offre disponible pour le moment.</div>
@endif
@endsection
