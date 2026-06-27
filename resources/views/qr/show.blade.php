<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>QR Code — Commande #{{ $order->shopify_order_id }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .qr-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 8px;
            border-radius: 16px;
        }
        .qr-inner {
            background: white;
            padding: 24px;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-blue-600 text-white text-2xl font-bold mb-4">
                    QR
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Votre QR Code</h1>
                <p class="text-slate-600 mt-1">Commande #{{ $order->shopify_order_id }}</p>
            </div>

            <!-- QR Code -->
            <div class="qr-container mb-8">
                <div class="qr-inner">
                    <div class="text-center mb-4">
                        <p class="text-sm text-slate-500">Scannez ce code lors de votre prestation</p>
                    </div>
                    <div class="flex justify-center mb-4">
                        <img src="data:image/png;base64,{{ $qrCodeImage }}" 
                             alt="QR Code pour la commande #{{ $order->shopify_order_id }}" 
                             class="w-64 h-64">
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-500">
                            Ce QR code expire le {{ $qrCode->expires_at?->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Instructions</h3>
                <ol class="space-y-2 text-sm text-slate-600 list-decimal list-inside">
                    <li>Présentez ce QR code au partenaire lors de votre prestation</li>
                    <li>Le partenaire scannera le code avec son application dédiée</li>
                    <li>Vous recevrez une confirmation instantanée de la validation</li>
                    <li>Conservez ce code jusqu'à la date d'expiration</li>
                </ol>
            </div>

            <!-- Customer Info -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Informations de la commande</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Client</span>
                        <span class="font-medium">{{ $order->customer_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Email</span>
                        <span class="font-medium">{{ $order->customer_email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Montant</span>
                        <span class="font-medium">{{ $order->formatted_amount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Statut</span>
                        <span class="font-medium">{{ ucfirst($order->status->value) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                <button onclick="window.print()" 
                        class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.879A3 3 0 0115 18.257V17.25m-6-10.5V5.25a3 3 0 01.879-2.122L16.5 3h-9l1.621.879A3 3 0 019 6.257V7.5" />
                    </svg>
                    Imprimer le QR Code
                </button>
                <a href="{{ route('qr.show', $qrCode->uuid) }}" download="qr-code-{{ $order->shopify_order_id }}.png" 
                   class="flex-1 bg-white border border-slate-300 text-slate-700 py-3 px-4 rounded-lg font-medium hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Télécharger
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-xs text-slate-500">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:support@app.com" class="text-blue-600 hover:underline">support@app.com</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Ajouter un favicon dynamique si nécessaire
        document.addEventListener('DOMContentLoaded', function() {
            // Code pour gérer l'impression ou d'autres interactions
            console.log('QR Code page loaded for order #{{ $order->shopify_order_id }}');
        });
    </script>
</body>
</html>