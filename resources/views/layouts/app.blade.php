<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

<div class="min-h-full">
    {{-- Top navigation --}}
    <nav class="bg-indigo-600">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-white md:hidden mr-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <a href="/" class="text-white font-bold text-xl">{{ config('app.name', 'ABPanel') }}</a>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-indigo-200 text-sm hidden sm:inline">{{ auth()->user()->full_name }}</span>

                    {{-- Notification bell --}}
                    @php
                        $unreadCount = auth()->user()->appNotifications()->whereNull('read_at')->count();
                        $recentNotifs = auth()->user()->appNotifications()->latest()->limit(8)->get();
                    @endphp
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="relative text-indigo-200 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center leading-none">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                            @endif
                        </button>

                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="font-semibold text-gray-800 text-sm">Notifications</span>
                                @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}" @submit="open = false">
                                    @csrf
                                    <button class="text-xs text-indigo-600 hover:underline">Tout lire</button>
                                </form>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                                @forelse($recentNotifs as $notif)
                                <a href="{{ route('notifications.read', $notif) }}"
                                   class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition {{ $notif->isUnread() ? 'bg-indigo-50/30' : '' }}">
                                    <span class="text-lg shrink-0 mt-0.5">{{ $notif->icon() }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 leading-tight truncate">{{ $notif->title }}</p>
                                        @if($notif->body)
                                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $notif->body }}</p>
                                        @endif
                                        <p class="text-xs text-gray-300 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                    @if($notif->isUnread())
                                    <div class="shrink-0 w-2 h-2 rounded-full bg-indigo-500 mt-1.5"></div>
                                    @endif
                                </a>
                                @empty
                                <p class="px-4 py-6 text-sm text-gray-400 text-center">Aucune notification</p>
                                @endforelse
                            </div>
                            <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50">
                                <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 hover:underline">
                                    Voir toutes les notifications →
                                </a>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="text-indigo-200 hover:text-white text-sm">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-indigo-200 hover:text-white text-sm">Déconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Impersonation banner --}}
    @if(session('impersonating_admin_id'))
    <div class="bg-orange-500 text-white text-sm px-4 py-2 flex items-center justify-between">
        <span>Vous consultez l'espace de <strong>{{ auth()->user()->full_name }}</strong> ({{ auth()->user()->email }})</span>
        <a href="{{ route('client.impersonate.stop') }}"
           class="ml-4 px-3 py-1 bg-white text-orange-600 font-semibold rounded hover:bg-orange-50 transition text-xs">
            ← Retour à mon compte admin
        </a>
    </div>
    @endif

    <div class="flex">
        {{-- Sidebar --}}
        <aside class="hidden md:flex md:flex-col md:w-64 md:min-h-screen bg-white shadow-sm" :class="sidebarOpen ? 'flex' : 'hidden'">
            @yield('sidebar')
        </aside>

        {{-- Main content --}}
        <main class="flex-1 p-6">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800 text-sm border border-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800 text-sm border border-red-200">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800 text-sm border border-red-200">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
