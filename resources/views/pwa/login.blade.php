<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A2164">
    <title>Connexion - {{ config('app.name', 'QR Scanner') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="manifest" href="/pwa/manifest.json">

    <style>
        body {
            background-color: #0A2164;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1B56F5 0%, #0A2164 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="logo mx-auto mb-4">QR</div>
            <h1 class="text-2xl font-bold text-white mb-2">QR Shopify Scanner</h1>
            <p class="text-blue-200">Connectez-vous pour scanner les QR codes</p>
        </div>

        <div class="bg-white rounded-xl shadow-2xl p-6">
            {{-- Erreurs Laravel (depuis ValidationException ou redirect()->withErrors) --}}
            @if ($errors->any())
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('pwa.login.attempt') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email"
                           inputmode="email"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-rose-500 @enderror">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-rose-500 @enderror">
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                    Se connecter
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <p class="text-xs text-blue-200">
                Besoin d'aide ? Contactez l'administrateur à
                <a href="mailto:{{ config('mail.from.address', 'support@app.com') }}" class="underline">support</a>
            </p>
        </div>
    </div>

    {{-- Service Worker enregistré après login (page suivante) pour éviter
         de cacher le formulaire lui-même. --}}
</body>
</html>