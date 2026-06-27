<!DOCTYPE html>
<html class="light" lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $__env->yieldContent('title', 'Paramètres Système'); ?> — <?php echo e(config('app.name')); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php
        use Carbon\Carbon;
    ?>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-fixed": "#c9e6ff",
                        "tertiary": "#005e88",
                        "surface": "#f7f9fb",
                        "tertiary-container": "#0078ab",
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#57657a",
                        "primary-fixed": "#92f6cf",
                        "surface-bright": "#f7f9fb",
                        "on-error-container": "#93000a",
                        "surface-dim": "#d8dadc",
                        "outline": "#6e7a73",
                        "on-primary-container": "#d6ffeb",
                        "tertiary-fixed-dim": "#89ceff",
                        "on-primary-fixed-variant": "#00513c",
                        "surface-container-highest": "#e0e3e5",
                        "primary-fixed-dim": "#75d9b3",
                        "on-surface-variant": "#3e4944",
                        "secondary": "#515f74",
                        "surface-container": "#eceef0",
                        "on-tertiary-container": "#f0f7ff",
                        "error": "#ba1a1a",
                        "on-tertiary-fixed": "#001e2f",
                        "on-background": "#191c1e",
                        "error-container": "#ffdad6",
                        "surface-container-high": "#e6e8ea",
                        "on-error": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-fixed-variant": "#3a485b",
                        "outline-variant": "#bdc9c2",
                        "primary-container": "#008060",
                        "on-primary": "#ffffff",
                        "inverse-surface": "#2d3133",
                        "surface-variant": "#e0e3e5",
                        "on-tertiary": "#ffffff",
                        "surface-tint": "#006c50",
                        "on-tertiary-fixed-variant": "#004c6e",
                        "inverse-primary": "#75d9b3",
                        "secondary-fixed": "#d5e3fc",
                        "secondary-container": "#d5e3fc",
                        "primary": "#00654b",
                        "on-primary-fixed": "#002116",
                        "on-surface": "#191c1e",
                        "background": "#f7f9fb",
                        "secondary-fixed-dim": "#b9c7df",
                        "inverse-on-surface": "#eff1f3",
                        "surface-container-low": "#f2f4f6",
                        "on-secondary-fixed": "#0d1c2e"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "xl": "32px",
                        "gutter": "16px",
                        "base": "4px",
                        "md": "16px",
                        "lg": "24px",
                        "xs": "8px",
                        "sm": "12px",
                        "container-margin": "24px"
                    },
                    "fontFamily": {
                        "headline-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "label-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "button-text": ["Inter"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
        }
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-surface text-on-surface flex min-h-screen overflow-hidden">
    <!-- Sidebar Navigation -->
    <aside class="hidden md:flex flex-col h-screen py-lg px-md bg-surface-container-low dark:bg-surface-container-lowest border-r border-outline-variant w-64 shrink-0">
        <div class="flex items-center gap-sm mb-xl">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined">qr_code_2</span>
            </div>
            <div>
                <h1 class="font-headline-md text-headline-md font-bold text-primary">QR Validator Pro</h1>
                <p class="text-[10px] uppercase tracking-widest text-on-surface-variant font-bold">Administration B2B</p>
            </div>
        </div>
        <nav class="flex-1 space-y-xs">
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors font-body-md text-body-md" href="<?php echo e(route('admin.dashboard')); ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Tableau de bord</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors font-body-md text-body-md" href="<?php echo e(route('admin.partners.index')); ?>">
                <span class="material-symbols-outlined">handshake</span>
                <span>Partenaires</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors font-body-md text-body-md" href="<?php echo e(route('admin.orders.index')); ?>">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>Commandes</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm text-primary dark:text-primary-fixed font-bold border-r-4 border-primary bg-surface-container-highest font-body-md text-body-md" href="<?php echo e(route('admin.settings')); ?>">
                <span class="material-symbols-outlined">settings</span>
                <span>Paramètres</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors font-body-md text-body-md" href="<?php echo e(route('admin.logs')); ?>">
                <span class="material-symbols-outlined">terminal</span>
                <span>Logs système</span>
            </a>
        </nav>
        <div class="mt-auto pt-md border-t border-outline-variant flex items-center gap-sm">
            <img class="w-10 h-10 rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCElNzTXAOPdBqgVe6E2a4eVggiqAQXVsCj2rLZwwtfFEibN_WAuh2EluOP-2S6eCtWHcrGasPoJaHbltkFbfDT4op6ylLTLRYsNVUtZ1Uiu5cCRwnXJMewNkjef1u6m-ZwU9P97V3S41vxwcd-5_b2HC-D1i82HRb4ms-WeURW3k4W8apZfK3fjd99xKR8VwZMVJHmRsKKHplqB-NmjsNKHEZZCH1zchs_2hcaHw2m9ECUxj1R9en85568MuUGit-A7z4KjSdYTATN">
            <div class="overflow-hidden">
                <p class="text-body-md font-bold truncate"><?php echo e(auth()->user()->name); ?></p>
                <p class="text-[11px] text-on-surface-variant truncate"><?php echo e(auth()->user()->email); ?></p>
            </div>
        </div>
    </aside>
    
    <div class="flex-1 flex flex-col min-h-screen overflow-y-auto">
        <!-- Top Navigation -->
        <header class="flex justify-between items-center w-full px-lg h-16 bg-surface dark:bg-surface-dim border-b border-outline-variant sticky top-0 z-30">
            <div class="flex items-center gap-md">
                <button class="md:hidden p-sm text-on-surface">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="font-headline-md text-headline-md font-bold text-on-surface">Paramètres du système</h2>
            </div>
            <div class="flex items-center gap-sm">
                <button class="p-sm text-secondary hover:text-primary transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
                </button>
                <button class="p-sm text-secondary hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">account_circle</span>
                </button>
            </div>
        </header>
        
        <main class="flex-1 max-w-7xl w-full mx-auto p-lg space-y-lg pb-32">
            <!-- Page Tabs -->
            <div class="flex border-b border-outline-variant gap-lg overflow-x-auto no-scrollbar">
                <button class="pb-md px-xs font-label-md text-label-md border-b-2 border-primary text-primary transition-all whitespace-nowrap active-tab" id="btn-integration" onclick="switchTab('integration')">Intégration Shopify</button>
                <button class="pb-md px-xs font-label-md text-label-md border-b-2 border-transparent text-secondary hover:text-primary transition-all whitespace-nowrap" id="btn-notifications" onclick="switchTab('notifications')">Notifications</button>
                <button class="pb-md px-xs font-label-md text-label-md border-b-2 border-transparent text-secondary hover:text-primary transition-all whitespace-nowrap" id="btn-users" onclick="switchTab('users')">Gestion Utilisateurs</button>
                <button class="pb-md px-xs font-label-md text-label-md border-b-2 border-transparent text-secondary hover:text-primary transition-all whitespace-nowrap" id="btn-branding" onclick="switchTab('branding')">Branding & Design</button>
            </div>
            
            <!-- Tab: API Integration -->
            <section class="tab-content active animate-in fade-in slide-in-from-bottom-4 duration-500" id="tab-integration">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                    <div class="lg:col-span-2 space-y-lg">
                        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                            <div class="flex items-center gap-md mb-lg">
                                <div class="w-12 h-12 bg-[#95BF47]/10 flex items-center justify-center rounded-lg">
                                    <span class="material-symbols-outlined text-[#95BF47]">shopping_bag</span>
                                </div>
                                <div>
                                    <h3 class="font-headline-md text-headline-md text-on-surface">Configuration Shopify</h3>
                                    <p class="text-body-md text-on-surface-variant">Connectez votre boutique pour synchroniser les commandes et valider les codes QR en temps réel.</p>
                                </div>
                            </div>
                            <form method="POST" action="<?php echo e(route('admin.settings.test-shopify-connection')); ?>" class="space-y-md">
                                <?php echo csrf_field(); ?>
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Domaine de la boutique (.myshopify.com)</label>
                                    <input class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-2 focus:ring-tertiary focus:border-primary outline-none transition-all" 
                                           placeholder="ma-boutique.myshopify.com" 
                                           type="text" 
                                           name="shop_domain" 
                                           value="<?php echo e(config('shopify.shop_domain') ?? ''); ?>" required>
                                </div>
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Clé API d'accès administrateur</label>
                                    <div class="relative">
                                        <input class="w-full px-md py-sm pr-12 rounded-lg border border-outline-variant focus:ring-2 focus:ring-tertiary focus:border-primary outline-none transition-all" 
                                               type="password" 
                                               name="api_key" 
                                               value="<?php echo e(config('shopify.api_key') ?? ''); ?>" required>
                                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant" type="button">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-on-surface-variant">Requis pour synchroniser le catalogue produits et l'historique des commandes.</p>
                                </div>
                                <div class="flex items-center gap-md pt-sm">
                                    <button class="px-lg py-sm bg-primary text-on-primary rounded-lg font-button-text hover:bg-primary/90 transition-all shadow-md" type="submit">Tester la connexion</button>
                                    <button class="px-lg py-sm border border-outline-variant text-on-surface-variant rounded-lg font-button-text hover:bg-surface-container-high transition-all" type="button">Générer un nouveau Webhook</button>
                                </div>
                                <div id="connection-test-result" class="mt-md p-md rounded-lg" style="display: none;"></div>
                            </form>
                        </div>
                        
                        <div class="bg-surface-container-low p-lg rounded-xl border border-outline-variant">
                            <h4 class="font-body-lg font-bold mb-md">Statut de la synchronisation</h4>
                            <?php
                                // Vérifier si les informations de configuration Shopify sont présentes
                                $shopDomain = config('shopify.shop_domain');
                                $apiKey = config('shopify.api_key');
                                $isConfigured = !empty($shopDomain) && !empty($apiKey);
                                
                                $syncStatus = [
                                    'is_connected' => $isConfigured,
                                    'last_sync' => $isConfigured ? now()->subMinutes(2)->format('Y-m-d H:i:s') : null,
                                    'status' => $isConfigured ? 'ACTIF' : 'INACTIF',
                                    'status_color' => $isConfigured ? '#008060' : '#ba1a1a',
                                    'message' => $isConfigured ? 'Connecté à Shopify' : 'Configuration Shopify requise'
                                ];
                            ?>
                            <div class="flex items-center justify-between p-md bg-surface-container-lowest rounded-lg border border-outline-variant">
                                <div class="flex items-center gap-md">
                                    <span class="w-3 h-3 rounded-full animate-pulse" style="background-color: <?php echo e($syncStatus['status_color']); ?>"></span>
                                    <div>
                                        <p class="text-body-md font-bold"><?php echo e($syncStatus['message']); ?></p>
                                        <?php if($syncStatus['last_sync']): ?>
                                            <p class="text-xs text-on-surface-variant">Dernière mise à jour : <?php echo e(Carbon::parse($syncStatus['last_sync'])->diffForHumans()); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="px-sm py-xs rounded-full text-xs font-bold" style="background-color: <?php echo e($syncStatus['status_color']); ?>10; color: <?php echo e($syncStatus['status_color']); ?>"><?php echo e($syncStatus['status']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-lg">
                        <div class="bg-primary-container p-lg rounded-xl text-on-primary-container">
                            <h4 class="font-body-lg font-bold mb-xs">Besoin d'aide ?</h4>
                            <p class="text-body-md mb-md opacity-90">Consultez notre guide d'intégration Shopify pour obtenir vos clés API en moins de 2 minutes.</p>
                            <a class="flex items-center gap-sm font-button-text hover:underline" href="#">
                                Voir la documentation <span class="material-symbols-outlined text-sm">open_in_new</span>
                            </a>
                        </div>
                        
                        <div class="p-lg rounded-xl border-2 border-dashed border-outline-variant flex flex-col items-center justify-center text-center space-y-md">
                            <span class="material-symbols-outlined text-outline-variant text-4xl">extension</span>
                            <p class="text-body-md text-on-surface-variant">D'autres intégrations (WooCommerce, Magento) arrivent prochainement.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Tab: Notifications -->
            <section class="tab-content animate-in fade-in slide-in-from-bottom-4 duration-500" id="tab-notifications">
                <div class="max-w-3xl space-y-lg">
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant">
                        <h3 class="font-headline-md text-headline-md mb-md">Alertes de Validation</h3>
                        <div class="divide-y divide-outline-variant">
                            <div class="py-md flex items-center justify-between">
                                <div>
                                    <p class="font-body-lg font-bold">Notifications Push (Mobile)</p>
                                    <p class="text-body-md text-on-surface-variant">Recevez une alerte sur votre terminal lors de chaque scan.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input checked="" class="sr-only peer" type="checkbox">
                                    <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div class="py-md flex items-center justify-between">
                                <div>
                                    <p class="font-body-lg font-bold">Rapports Quotidiens</p>
                                    <p class="text-body-md text-on-surface-variant">Résumé par email des scans validés et des erreurs critiques.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input class="sr-only peer" type="checkbox">
                                    <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div class="py-md flex items-center justify-between">
                                <div>
                                    <p class="font-body-lg font-bold">Alerte Fraude</p>
                                    <p class="text-body-md text-on-surface-variant">Notifications instantanées en cas de scans multiples d'un même code.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input checked="" class="sr-only peer" type="checkbox">
                                    <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant">
                        <h3 class="font-headline-md text-headline-md mb-md">Canaux de Sortie</h3>
                        <div class="space-y-md">
                            <div class="flex items-center gap-md p-md border border-outline-variant rounded-lg">
                                <div class="w-10 h-10 bg-secondary-container flex items-center justify-center rounded-lg">
                                    <span class="material-symbols-outlined text-secondary">mail</span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold">Email Principal</p>
                                    <p class="text-sm text-on-surface-variant"><?php echo e(auth()->user()->email); ?></p>
                                </div>
                                <button class="text-primary font-button-text">Modifier</button>
                            </div>
                            <div class="flex items-center gap-md p-md border border-outline-variant rounded-lg">
                                <div class="w-10 h-10 bg-tertiary-fixed flex items-center justify-center rounded-lg">
                                    <span class="material-symbols-outlined text-tertiary">message</span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold">SMS / WhatsApp</p>
                                    <p class="text-sm text-on-surface-variant">Non configuré</p>
                                </div>
                                <button class="text-primary font-button-text">Configurer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Tab: User Management -->
            <section class="tab-content animate-in fade-in slide-in-from-bottom-4 duration-500" id="tab-users">
                <div class="space-y-lg">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
                        <div>
                            <h3 class="font-headline-md text-headline-md">Administrateurs & Opérateurs</h3>
                            <p class="text-body-md text-on-surface-variant">Gérez les accès de votre équipe sur les terminaux de scan.</p>
                        </div>
                        <button class="px-lg py-sm bg-primary text-on-primary rounded-lg font-button-text flex items-center gap-sm shadow-md">
                            <span class="material-symbols-outlined">person_add</span> Inviter un membre
                        </button>
                    </div>
                    
                    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-surface-container-low border-b border-outline-variant">
                                <tr>
                                    <th class="px-lg py-md font-label-md text-on-surface-variant">UTILISATEUR</th>
                                    <th class="px-lg py-md font-label-md text-on-surface-variant">RÔLE</th>
                                    <th class="px-lg py-md font-label-md text-on-surface-variant">DERNIÈRE ACTIVITÉ</th>
                                    <th class="px-lg py-md font-label-md text-on-surface-variant text-right">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                <tr>
                                    <td class="px-lg py-md">
                                        <div class="flex items-center gap-md">
                                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">AP</div>
                                            <div>
                                                <p class="font-bold"><?php echo e(auth()->user()->name); ?></p>
                                                <p class="text-xs text-on-surface-variant"><?php echo e(auth()->user()->email); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md">
                                        <span class="px-sm py-xs bg-tertiary-fixed text-tertiary text-xs font-bold rounded-full">PROPRIÉTAIRE</span>
                                    </td>
                                    <td class="px-lg py-md text-body-md">En ligne</td>
                                    <td class="px-lg py-md text-right">
                                        <button class="text-on-surface-variant"><span class="material-symbols-outlined">more_vert</span></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Tab: Branding -->
            <section class="tab-content animate-in fade-in slide-in-from-bottom-4 duration-500" id="tab-branding">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-lg">
                        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant">
                            <h3 class="font-headline-md text-headline-md mb-md">Identité Visuelle</h3>
                            <div class="space-y-lg">
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Logo de l'entreprise</label>
                                    <div class="flex items-center gap-lg p-lg border-2 border-dashed border-outline-variant rounded-xl bg-surface-container-low/50">
                                        <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center border border-outline-variant shadow-sm">
                                            <span class="material-symbols-outlined text-outline-variant text-3xl">image</span>
                                        </div>
                                        <div class="space-y-sm">
                                            <button class="px-md py-xs bg-white border border-outline-variant text-on-surface rounded-lg font-button-text shadow-sm hover:bg-surface transition-all">Télécharger</button>
                                            <p class="text-xs text-on-surface-variant">PNG ou SVG. Max 2MB. Taille recommandée 512x512px.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Couleur Primaire (UI & Scans)</label>
                                    <div class="flex items-center gap-md">
                                        <input class="w-12 h-12 p-1 bg-white border border-outline-variant rounded-lg cursor-pointer" type="color" value="#008060">
                                        <input class="flex-1 px-md py-sm rounded-lg border border-outline-variant font-mono text-sm uppercase" type="text" value="#008060">
                                    </div>
                                    <p class="text-xs text-on-surface-variant">Cette couleur sera utilisée pour les boutons, les états de validation et l'interface mobile.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant">
                            <h3 class="font-headline-md text-headline-md mb-md">Messages Personnalisés</h3>
                            <div class="space-y-md">
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Message de succès (Scan)</label>
                                    <input class="w-full px-md py-sm rounded-lg border border-outline-variant outline-none focus:ring-2 focus:ring-primary" placeholder="Billet validé. Bienvenue !" type="text">
                                </div>
                                <div class="space-y-xs">
                                    <label class="block text-body-md font-bold text-on-surface">Message d'erreur (Scan)</label>
                                    <input class="w-full px-md py-sm rounded-lg border border-outline-variant outline-none focus:ring-2 focus:ring-error" placeholder="Code invalide ou déjà utilisé." type="text">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-lg">
                        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant sticky top-24">
                            <h4 class="font-body-lg font-bold mb-md">Aperçu Mobile</h4>
                            <div class="aspect-[9/19] w-full max-w-[280px] mx-auto bg-black rounded-[3rem] p-3 shadow-2xl relative border-[6px] border-slate-800">
                                <div class="w-32 h-6 bg-black absolute top-0 left-1/2 -translate-x-1/2 rounded-b-2xl z-10"></div>
                                <div class="bg-white w-full h-full rounded-[2.2rem] overflow-hidden flex flex-col">
                                    <!-- Mobile Preview Top Bar -->
                                    <div class="h-14 flex items-center justify-between px-md pt-4">
                                        <span class="material-symbols-outlined text-primary text-xl">menu</span>
                                        <div class="w-6 h-6 bg-primary rounded-full"></div>
                                    </div>
                                    <!-- Mobile Preview Content -->
                                    <div class="flex-1 p-md flex flex-col items-center justify-center text-center space-y-md">
                                        <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-primary text-5xl animate-bounce">check_circle</span>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-lg">Billet validé.</h5>
                                            <p class="text-xs text-on-surface-variant">Bienvenue à l'événement !</p>
                                        </div>
                                        <div class="w-full bg-surface-container-low p-md rounded-xl space-y-xs">
                                            <div class="h-2 w-1/2 bg-surface-container-highest rounded-full"></div>
                                            <div class="h-2 w-3/4 bg-surface-container-highest rounded-full"></div>
                                        </div>
                                    </div>
                                    <!-- Mobile Preview Button -->
                                    <div class="p-md">
                                        <div class="h-10 w-full bg-primary rounded-lg"></div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-center text-xs text-on-surface-variant mt-md">Aperçu temps réel de l'application de scan</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <!-- Fixed Save Bar -->
        <div class="fixed bottom-0 right-0 w-full md:w-[calc(100%-16rem)] p-lg bg-surface/80 backdrop-blur-md border-t border-outline-variant z-40">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <p class="text-body-md text-on-surface-variant hidden sm:block">Vous avez des modifications non enregistrées.</p>
                <div class="flex items-center gap-md w-full sm:w-auto">
                    <button class="flex-1 sm:flex-none px-lg py-sm text-secondary font-button-text hover:bg-surface-container-high rounded-lg transition-colors">Réinitialiser</button>
                    <button class="flex-1 sm:flex-none px-xl py-sm bg-primary text-on-primary rounded-lg font-button-text shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all">Enregistrer les modifications</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-xs pb-safe h-20 bg-surface dark:bg-inverse-surface shadow-lg rounded-t-xl">
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-surface-variant px-4 py-1" href="<?php echo e(route('admin.dashboard')); ?>">
            <span class="material-symbols-outlined">qr_code_scanner</span>
            <span class="font-label-md text-label-md">Scanner</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-surface-variant px-4 py-1" href="<?php echo e(route('admin.orders.index')); ?>">
            <span class="material-symbols-outlined">history</span>
            <span class="font-label-md text-label-md">Historique</span>
        </a>
        <a class="flex flex-col items-center justify-center bg-primary-container dark:bg-primary text-on-primary-container dark:text-on-primary rounded-xl px-4 py-1" href="<?php echo e(route('admin.settings')); ?>">
            <span class="material-symbols-outlined">person</span>
            <span class="font-label-md text-label-md">Profil</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-surface-variant px-4 py-1" href="<?php echo e(route('admin.support')); ?>">
            <span class="material-symbols-outlined">help</span>
            <span class="font-label-md text-label-md">Aide</span>
        </a>
    </nav>
    
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active style from all buttons
            document.querySelectorAll('[id^="btn-"]').forEach(btn => {
                btn.classList.remove('border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-secondary');
            });
            
            // Show selected tab content
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Add active style to selected button
            const activeBtn = document.getElementById('btn-' + tabId);
            activeBtn.classList.remove('border-transparent', 'text-secondary');
            activeBtn.classList.add('border-primary', 'text-primary');
        }
        
        // Handle Shopify connection test form submission
        document.addEventListener('DOMContentLoaded', function() {
            const shopifyForm = document.querySelector('form[action*="test-shopify-connection"]');
            if (shopifyForm) {
                shopifyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const form = e.target;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.textContent;
                    
                    // Show loading state
                    submitBtn.textContent = 'Test en cours...';
                    submitBtn.disabled = true;
                    
                    // Submit form via AJAX
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            shop_domain: form.querySelector('input[name="shop_domain"]').value,
                            api_key: form.querySelector('input[name="api_key"]').value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Show success message
                        const resultDiv = document.getElementById('connection-test-result');
                        resultDiv.textContent = '✅ Succès: ' + data.message;
                        resultDiv.style.display = 'block';
                        resultDiv.style.backgroundColor = '#d6ffeb';
                        resultDiv.style.color = '#00513c';
                        resultDiv.style.border = '1px solid #008060';
                        console.log('Shopify connection test successful:', data);
                        
                        // Refresh sync status after successful test
                        refreshSyncStatus();
                    })
                    .catch(error => {
                        // Show error message
                        const resultDiv = document.getElementById('connection-test-result');
                        resultDiv.textContent = '❌ Erreur: ' + (error.message || 'Échec du test de connexion');
                        resultDiv.style.display = 'block';
                        resultDiv.style.backgroundColor = '#ffdad6';
                        resultDiv.style.color = '#93000a';
                        resultDiv.style.border = '1px solid #ba1a1a';
                        console.error('Shopify connection test failed:', error);
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.textContent = originalBtnText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Function to refresh sync status
            function refreshSyncStatus() {
                fetch('<?php echo e(route("admin.settings.shopify-sync-status")); ?>', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const syncStatusDiv = document.querySelector('.bg-surface-container-lowest .flex.items-center.justify-between');
                    if (syncStatusDiv) {
                        const statusDot = syncStatusDiv.querySelector('.w-3.h-3');
                        const statusMessage = syncStatusDiv.querySelector('.text-body-md.font-bold');
                        const statusTime = syncStatusDiv.querySelector('.text-xs.text-on-surface-variant');
                        const statusBadge = syncStatusDiv.querySelector('span:last-child');
                        
                        if (statusDot) statusDot.style.backgroundColor = data.status_color;
                        if (statusMessage) statusMessage.textContent = data.message;
                        
                        if (data.last_sync && statusTime) {
                            const lastSyncDate = new Date(data.last_sync);
                            const now = new Date();
                            const diffMinutes = Math.floor((now - lastSyncDate) / (1000 * 60));
                            statusTime.textContent = `Dernière mise à jour : il y a ${diffMinutes} minute${diffMinutes > 1 ? 's' : ''}`;
                        }
                        
                        if (statusBadge) {
                            statusBadge.textContent = data.status;
                            statusBadge.style.backgroundColor = data.status_color + '10';
                            statusBadge.style.color = data.status_color;
                        }
                    }
                })
                .catch(error => {
                    console.error('Failed to refresh sync status:', error);
                });
            }
            
            // Refresh sync status every 30 seconds
            setInterval(refreshSyncStatus, 30000);
        });
        
        // Handle micro-interactions
        document.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('mousedown', () => btn.classList.add('opacity-80'));
            btn.addEventListener('mouseup', () => btn.classList.remove('opacity-80'));
            btn.addEventListener('mouseleave', () => btn.classList.remove('opacity-80'));
        });
    </script>
</body>
</html><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/admin/system_settings.blade.php ENDPATH**/ ?>