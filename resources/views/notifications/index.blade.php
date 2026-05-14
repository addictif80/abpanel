@extends('layouts.app')
@section('title', 'Notifications')
@section('sidebar')
    @if(auth()->user()->is_admin)
        <x-admin-sidebar />
    @else
        <x-client-sidebar />
    @endif
@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $notifications->total() }} notification(s)</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Tout marquer comme lu
        </button>
    </form>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-50">
    @forelse($notifications as $notif)
    <div class="flex items-start gap-4 px-5 py-4 {{ $notif->isUnread() ? 'bg-indigo-50/30' : '' }} hover:bg-gray-50 transition">
        <div class="shrink-0 text-xl mt-0.5">{{ $notif->icon() }}</div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold {{ $notif->isUnread() ? 'text-gray-900' : 'text-gray-600' }}">
                        {{ $notif->title }}
                        @if($notif->isUnread())
                        <span class="inline-block w-2 h-2 rounded-full bg-indigo-500 ml-1 align-middle"></span>
                        @endif
                    </p>
                    @if($notif->body)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $notif->body }}</p>
                    @endif
                </div>
                <span class="shrink-0 text-xs text-gray-400">{{ $notif->created_at->diffForHumans() }}</span>
            </div>
            @if($notif->url)
            <a href="{{ route('notifications.read', $notif) }}" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">
                Voir →
            </a>
            @endif
        </div>
    </div>
    @empty
    <div class="px-5 py-12 text-center text-gray-400">
        <p class="text-4xl mb-3">🔔</p>
        <p class="text-sm">Aucune notification pour l'instant.</p>
    </div>
    @endforelse
</div>

@if($notifications->hasPages())
<div class="mt-4">{{ $notifications->links() }}</div>
@endif
@endsection
