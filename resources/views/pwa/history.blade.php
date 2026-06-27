<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Historique - QR Shopify</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="manifest" href="/pwa/manifest.json">

    @include('pwa.partials.pwa-head')

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/pwa/service-worker.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>

    <style>
        body {
            background-color: #0A2164;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1B56F5 0%, #0A2164 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .scan-item {
            transition: transform 0.2s, background-color 0.2s;
        }
        .scan-item:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <button id="backButton" class="p-2 rounded-lg hover:bg-blue-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <div class="logo"></div>
            <div class="w-12"></div> <!-- Espaceur -->
        </div>

        <!-- Titre -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-2">Historique des scans</h1>
            <p id="scanCount" class="text-blue-200 text-sm">0 scan(s)</p>
        </div>

        <!-- Liste des scans -->
        <div id="scansList" class="space-y-3 mb-8">
            <!-- Les scans seront ajoutés ici dynamiquement -->
        </div>

        <!-- Bouton de retour -->
        <div class="fixed bottom-8 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
            <button id="scanButton" class="w-full bg-white text-blue-600 px-6 py-3 rounded-lg font-medium shadow-lg hover:bg-blue-50 transition">
                Retour au scanner
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const backButton = document.getElementById('backButton');
            const scanButton = document.getElementById('scanButton');
            const scansList = document.getElementById('scansList');
            const scanCount = document.getElementById('scanCount');

            try {
                // Récupérer l'historique des scans (window.pwaFetch pose Bearer + CSRF)
                const response = await window.pwaFetch('/api/validations/my', {
                    method: 'GET'
                });

                if (response.ok) {
                    const data = await response.json();
                    const scans = data.validations || data || [];

                    // Mettre à jour le compteur
                    scanCount.textContent = `${scans.length} scan(s)`;

                    // Afficher les scans
                    if (scans.length > 0) {
                        scans.forEach(scan => {
                            const scanElement = document.createElement('div');
                            scanElement.className = 'scan-item bg-white bg-opacity-10 rounded-xl p-4';

                            const statusColor = scan.status === 'valid' ? 'text-emerald-400' : 'text-rose-400';
                            const statusText = scan.status === 'valid' ? 'Validé' : 'Échoué';

                            scanElement.innerHTML = `
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full ${statusColor}"></div>
                                        <span class="text-sm">${statusText}</span>
                                    </div>
                                    <span class="text-xs text-blue-200">${new Date(scan.scanned_at).toLocaleString()}</span>
                                </div>
                                <div class="text-sm text-blue-100">Commande #${scan.order?.shopify_order_id || 'N/A'}</div>
                            `;

                            scansList.appendChild(scanElement);
                        });
                    } else {
                        scansList.innerHTML = `
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-blue-200">Aucun scan enregistré.</p>
                            </div>
                        `;
                    }
                } else {
                    throw new Error('Failed to fetch scan history');
                }

            } catch (error) {
                console.error('Error fetching scan history:', error);
                scansList.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-rose-400">Erreur de chargement de l'historique.</p>
                        <button id="retryButton" class="mt-4 text-blue-400 underline text-sm">Réessayer</button>
                    </div>
                `;

                document.getElementById('retryButton')?.addEventListener('click', () => {
                    window.location.reload();
                });
            }

            // Gestion des boutons
            backButton.addEventListener('click', () => {
                window.location.href = '/pwa/scan';
            });

            scanButton.addEventListener('click', () => {
                window.location.href = '/pwa/scan';
            });
        });
    </script>
</body>
</html>