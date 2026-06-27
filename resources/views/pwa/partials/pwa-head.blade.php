{{--
    Partial head PWA : injecté en haut de chaque vue authentifiée.

    Responsabilités :
    1. CSRF token (formulaires web si besoin)
    2. Token Sanctum (Bearer pour fetch vers /api/*)
    3. CSRF header global pour fetch (Laravel attend X-XSRF-TOKEN
       décodé depuis le cookie XSRF-TOKEN — on le rend transparent ici)
    4. Helper window.pwaFetch() qui pose automatiquement les bons headers

    Source du token Sanctum : session('pwa.api_token') posé par
    PwaAuthController::login(). Après consommation, on le transfère
    dans localStorage pour survivre aux refresh et le supprime de la
    session pour éviter qu'il transite sur chaque réponse.
--}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="api-token" content="{{ trim(session('pwa.api_token', '')) }}">

@once
    @push('pwa-head')
        <script>
            (function () {
                'use strict';

                var meta = document.querySelector('meta[name="api-token"]');
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');

                // -------------------------------------------------------------------------
                // Token Sanctum : session -> localStorage
                // On le transfère une seule fois, puis on demande au serveur de le retirer
                // de la session via un fetch idempotent.
                // -------------------------------------------------------------------------
                var sessionToken = meta ? meta.getAttribute('content') : '';
                if (sessionToken) {
                    try {
                        localStorage.setItem('sanctum_token', sessionToken);
                        // Retrait du token de la session pour qu'il n'apparaisse plus
                        // dans les meta des pages suivantes.
                        fetch('/pwa/api-token-consume', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        }).catch(function () { /* silencieux */ });
                    } catch (e) {
                        console.warn('[PWA] localStorage indisponible', e);
                    }
                }

                var token = (function () {
                    try { return localStorage.getItem('sanctum_token') || ''; }
                    catch (e) { return sessionToken || ''; }
                })();

                // -------------------------------------------------------------------------
                // Décodage XSRF-TOKEN (cookie encodé URL par Laravel)
                // -------------------------------------------------------------------------
                function readCookie(name) {
                    var match = document.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'));
                    return match ? decodeURIComponent(match[2]) : '';
                }

                // -------------------------------------------------------------------------
                // Helper fetch : pose automatiquement Authorization, Accept, CSRF.
                // -------------------------------------------------------------------------
                window.pwaFetch = function (url, options) {
                    options = options || {};
                    options.headers = options.headers || {};
                    options.credentials = 'same-origin';

                    if (token) {
                        options.headers['Authorization'] = 'Bearer ' + token;
                    }
                    if (!options.headers['Accept']) {
                        options.headers['Accept'] = 'application/json';
                    }
                    if (options.method && options.method.toUpperCase() !== 'GET') {
                        options.headers['X-Requested-With'] = 'XMLHttpRequest';
                        options.headers['X-XSRF-TOKEN'] = readCookie('XSRF-TOKEN');
                        if (!options.headers['Content-Type'] && options.body && typeof options.body === 'string') {
                            options.headers['Content-Type'] = 'application/json';
                        }
                    }

                    return fetch(url, options).then(function (response) {
                        // Token révoqué ou expiré → on déconnecte.
                        if (response.status === 401) {
                            try { localStorage.removeItem('sanctum_token'); } catch (e) {}
                            window.location.href = '/pwa/login';
                        }
                        return response;
                    });
                };
            })();
        </script>
    @endpush
@endonce