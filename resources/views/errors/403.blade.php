<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — ABPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-100 mb-6">
            <svg class="w-10 h-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">403</h1>
        <p class="text-lg font-semibold text-gray-700 mb-2">Accès refusé</p>
        <p class="text-gray-500 mb-8">{{ $exception->getMessage() ?: "Vous n'avez pas les droits pour accéder à cette page." }}</p>
        <a href="{{ url()->previous('/') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
            ← Retour
        </a>
    </div>
</body>
</html>
