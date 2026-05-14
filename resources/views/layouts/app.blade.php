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
                    <span class="text-indigo-200 text-sm">{{ auth()->user()->full_name }}</span>
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
