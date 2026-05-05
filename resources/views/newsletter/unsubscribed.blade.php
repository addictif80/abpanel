<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <title>Désabonnement — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center">
<div class="text-center max-w-md px-6">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h1 class="text-xl font-bold text-gray-900 mb-2">Vous êtes désabonné</h1>
    <p class="text-gray-500 text-sm">Vous ne recevrez plus nos newsletters. Vous pouvez vous réabonner depuis votre espace client.</p>
    <a href="{{ url('/') }}" class="mt-6 inline-block px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Retour au site
    </a>
</div>
</body>
</html>
