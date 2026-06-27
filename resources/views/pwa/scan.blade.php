<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Scanner QR - QR Shopify</title>
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
        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 16px;
        }
        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            border: 4px solid rgba(27, 86, 245, 0.5);
            border-radius: 12px;
        }
        .scanner-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 33, 100, 0.3);
            clip-path: polygon(
                0% 0%, 100% 0%, 100% 20%, 80% 20%, 80% 80%, 20% 80%, 20% 20%, 0% 20%
            );
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
        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        .flash-button {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <button id="menuButton" class="p-2 rounded-lg hover:bg-blue-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="logo"></div>
            <button id="flashButton" class="flash-button w-12 h-12 rounded-full flex items-center justify-center hover:bg-white hover:bg-opacity-30 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </button>
        </div>

        <!-- Scanner -->
        <div class="scanner-container mb-6">
            <div class="scanner-overlay"></div>
            <video id="video" playsinline></video>
        </div>

        <!-- Instructions -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-semibold mb-2">Scannez un QR Code</h2>
            <p class="text-blue-200 text-sm">Positionnez le QR code dans la zone de scan</p>
        </div>

        <!-- Historique bouton -->
        <div class="fixed bottom-8 left-1/2 transform -translate-x-1/2">
            <button id="historyButton" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-medium shadow-lg hover:bg-blue-50 transition">
                Historique des scans
            </button>
        </div>
    </div>

    <!-- Menu latéral -->
    <div id="menu" class="fixed top-0 left-0 w-64 h-full bg-white text-slate-800 shadow-xl transform -translate-x-full transition-transform duration-300 z-50">
        <div class="p-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Menu</h3>
                <button id="closeMenu" class="p-1 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-2">
                <form id="logoutForm" method="POST" action="{{ route('pwa.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left p-3 rounded-lg hover:bg-slate-100 transition flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Overlay pour le menu -->
    <div id="menuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        // Variables globales
        let stream = null;
        let scanInterval = null;
        let flashEnabled = false;

        // DOM Elements
        const video = document.getElementById('video');
        const flashButton = document.getElementById('flashButton');
        const menuButton = document.getElementById('menuButton');
        const closeMenu = document.getElementById('closeMenu');
        const menu = document.getElementById('menu');
        const menuOverlay = document.getElementById('menuOverlay');
        const historyButton = document.getElementById('historyButton');
        const logoutForm = document.getElementById('logoutForm');

        // Initialisation
        async function init() {
            try {
                // Démarrer la caméra
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment',
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                video.srcObject = stream;
                video.play();

                // Démarrer le scan
                startScanning();

                // Gestion du flash
                flashButton.addEventListener('click', toggleFlash);

                // Gestion du menu
                menuButton.addEventListener('click', openMenu);
                closeMenu.addEventListener('click', closeMenuHandler);
                menuOverlay.addEventListener('click', closeMenuHandler);

                // Navigation
                historyButton.addEventListener('click', () => {
                    window.location.href = '/pwa/history';
                });

                // Nettoyage localStorage côté client (le serveur révoque les tokens)
                logoutForm.addEventListener('submit', () => {
                    try { localStorage.removeItem('sanctum_token'); } catch (e) {}
                });

            } catch (error) {
                console.error('Error initializing scanner:', error);
                alert('Impossible d\'accéder à la caméra. Veuillez vérifier les permissions.');
                window.location.href = '/pwa/login';
            }
        }

        // Démarrer le scanning
        function startScanning() {
            scanInterval = setInterval(async () => {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const context = canvas.getContext('2d');
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: 'dontInvert'
                    });

                    if (code) {
                        stopScanning();
                        await handleQRCode(code.data);
                    }
                }
            }, 500);
        }

        // Arrêter le scanning
        function stopScanning() {
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
        }

        // Gérer un QR code scanné
        async function handleQRCode(qrData) {
            try {
                // Extraire l'UUID du QR code
                const uuidMatch = qrData.match(/[a-f0-9]{64}/);
                if (!uuidMatch) {
                    showResult('error', 'QR Code invalide', 'Ce QR code n\'est pas valide pour cette application.');
                    return;
                }

                const uuid = uuidMatch[0];

                // window.pwaFetch pose automatiquement Bearer + CSRF + Accept.
                const response = await window.pwaFetch('/api/validate', {
                    method: 'POST',
                    body: JSON.stringify({ uuid: uuid })
                });

                const data = await response.json();

                // Rediriger vers la page de résultat avec les données
                localStorage.setItem('scanResult', JSON.stringify({
                    status: response.status,
                    data: data
                }));

                window.location.href = `/pwa/result`;

            } catch (error) {
                console.error('Error validating QR:', error);
                showResult('error', 'Erreur de validation', 'Une erreur est survenue lors de la validation.');
            } finally {
                // Redémarrer le scanning après 3 secondes
                setTimeout(() => {
                    startScanning();
                }, 3000);
            }
        }

        // Basculer le flash
        async function toggleFlash() {
            try {
                if (!stream) return;

                const track = stream.getVideoTracks()[0];
                if (!track) return;

                flashEnabled = !flashEnabled;

                try {
                    await track.applyConstraints({
                        advanced: [{ torch: flashEnabled }]
                    });

                    flashButton.classList.toggle('bg-yellow-500', flashEnabled);
                    flashButton.classList.toggle('bg-white', !flashEnabled);
                    flashButton.classList.toggle('bg-opacity-30', !flashEnabled);
                } catch (e) {
                    console.warn('Flash not available on this device');
                    alert('Le flash n\'est pas disponible sur cet appareil.');
                }

            } catch (error) {
                console.error('Error toggling flash:', error);
            }
        }

        // Ouvrir le menu
        function openMenu() {
            menu.classList.remove('-translate-x-full');
            menuOverlay.classList.remove('hidden');
        }

        // Fermer le menu
        function closeMenuHandler() {
            menu.classList.add('-translate-x-full');
            menuOverlay.classList.add('hidden');
        }

        // Nettoyer lors du départ
        window.addEventListener('beforeunload', () => {
            stopScanning();
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });

        // Initialiser l'application
        init();
    </script>
</body>
</html>