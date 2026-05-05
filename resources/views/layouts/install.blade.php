<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installation — ABPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gradient-to-br from-indigo-50 to-blue-100">
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-indigo-600">ABPanel</h1>
            <p class="mt-2 text-gray-500">Assistant d'installation</p>
        </div>

        {{-- Steps indicator --}}
        <div class="flex items-center justify-center mb-8">
            @php $steps = ['Bienvenue', 'Base de données', 'Administrateur', 'Terminé']; @endphp
            @foreach($steps as $i => $step)
                <div class="flex items-center">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                            {{ ($currentStep ?? 0) > $i ? 'bg-indigo-600 text-white' : (($currentStep ?? 0) == $i ? 'bg-indigo-600 text-white ring-4 ring-indigo-200' : 'bg-gray-200 text-gray-500') }}">
                            {{ ($currentStep ?? 0) > $i ? '✓' : $i + 1 }}
                        </div>
                        <span class="mt-1 text-xs text-gray-500 hidden sm:block">{{ $step }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="h-0.5 w-12 sm:w-24 mx-2 {{ ($currentStep ?? 0) > $i ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">
            @if($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-red-800 text-sm border border-red-200">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
