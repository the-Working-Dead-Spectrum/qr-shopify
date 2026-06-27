<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Résultat - QR Shopify</title>
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
        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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

        <!-- Résultat -->
        <div class="text-center">
            <div id="resultIcon" class="result-icon mx-auto mb-6"></div>
            <h2 id="resultTitle" class="text-2xl font-bold mb-2"></h2>
            <p id="resultMessage" class="text-blue-200 mb-8"></p>

            <!-- Détails supplémentaires -->
            <div id="resultDetails" class="bg-white bg-opacity-10 rounded-xl p-4 mb-8 text-left hidden">
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-blue-200">Commande</p>
                        <p id="orderId" class="font-mono text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-200">Date</p>
                        <p id="scanDate" class="text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-200">Statut</p>
                        <p id="qrStatus" class="text-white"></p>
                    </div>
                </div>
            </div>

            <!-- Bouton de retour -->
            <button id="continueButton" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-medium shadow-lg hover:bg-blue-50 transition">
                Continuer
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backButton = document.getElementById('backButton');
            const continueButton = document.getElementById('continueButton');
            const resultIcon = document.getElementById('resultIcon');
            const resultTitle = document.getElementById('resultTitle');
            const resultMessage = document.getElementById('resultMessage');
            const resultDetails = document.getElementById('resultDetails');
            const orderId = document.getElementById('orderId');
            const scanDate = document.getElementById('scanDate');
            const qrStatus = document.getElementById('qrStatus');

            // Récupérer le résultat du scan
            const scanResult = localStorage.getItem('scanResult');

            if (scanResult) {
                const result = JSON.parse(scanResult);
                const status = result.status;
                const data = result.data;

                // Définir l'icône et les couleurs en fonction du statut
                if (status === 200 && data.status === 'valid') {
                    // Succès
                    resultIcon.innerHTML = `<svg class="w-12 h-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
                    resultIcon.classList.add('bg-emerald-500', 'bg-opacity-20');
                    resultTitle.textContent = "Validation réussie";
                    resultMessage.textContent = "Le QR code a été validé avec succès.";

                    // Afficher les détails
                    if (data.order) {
                        orderId.textContent = `#${data.order.shopify_order_id}`;
                        scanDate.textContent = new Date().toLocaleString();
                        qrStatus.textContent = "Validé";
                        resultDetails.classList.remove('hidden');
                    }
                } else if (status === 409) {
                    // Déjà utilisé
                    resultIcon.innerHTML = `<svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                    resultIcon.classList.add('bg-amber-500', 'bg-opacity-20');
                    resultTitle.textContent = "Déjà utilisé";
                    resultMessage.textContent = "Ce QR code a déjà été utilisé.";
                } else if (status === 410) {
                    // Expiré
                    resultIcon.innerHTML = `<svg class="w-12 h-12 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;
                    resultIcon.classList.add('bg-rose-500', 'bg-opacity-20');
                    resultTitle.textContent = "QR Code expiré";
                    resultMessage.textContent = "Ce QR code a expiré et n'est plus valide.";
                } else if (status === 403) {
                    // Révoqué
                    resultIcon.innerHTML = `<svg class="w-12 h-12 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636M5.636 5.636l12.728 12.728"></path></svg>`;
                    resultIcon.classList.add('bg-rose-500', 'bg-opacity-20');
                    resultTitle.textContent = "QR Code révoqué";
                    resultMessage.textContent = "Ce QR code a été révoqué par l'administrateur.";
                } else {
                    // Erreur inconnue
                    resultIcon.innerHTML = `<svg class="w-12 h-12 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                    resultIcon.classList.add('bg-rose-500', 'bg-opacity-20');
                    resultTitle.textContent = "Erreur de validation";
                    resultMessage.textContent = data.message || "Une erreur est survenue lors de la validation.";
                }
            } else {
                // Aucun résultat trouvé
                resultIcon.innerHTML = `<svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                resultIcon.classList.add('bg-slate-500', 'bg-opacity-20');
                resultTitle.textContent = "Aucun résultat";
                resultMessage.textContent = "Aucun résultat de scan disponible.";
            }

            // Gestion des boutons
            backButton.addEventListener('click', () => {
                window.location.href = '/pwa/scan';
            });

            continueButton.addEventListener('click', () => {
                // Nettoyer le résultat et retourner au scanner
                localStorage.removeItem('scanResult');
                window.location.href = '/pwa/scan';
            });
        });
    </script>
</body>
</html>