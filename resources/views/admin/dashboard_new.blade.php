@extends('layouts.admin_new')

@section('title', 'Tableau de bord')

@section('content')
    <div class="max-w-7xl mx-auto space-y-xl" id="dashboard-content">
        <!-- KPI Cards (Section 1) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
            @include('components.kpi-card', [
                'title' => 'Ventes totales',
                'icon' => 'payments',
                'value' => number_format($shopifyData['totalSales'] ?? 0, 2, ',', ' ') . ' €',
                'trendColor' => 'primary',
                'trendIcon' => 'trending_up',
                'trendText' => '+12.5% vs mois dernier',
                'dataKpi' => 'totalSales'
            ])
            
            @include('components.kpi-card', [
                'title' => 'Scans aujourd\'hui',
                'icon' => 'qr_code_2',
                'value' => number_format($shopifyData['scansToday'] ?? 0, 0, ',', ' '),
                'trendColor' => 'primary',
                'trendIcon' => 'trending_up',
                'trendText' => '+5.2% vs hier',
                'dataKpi' => 'scansToday'
            ])
            
            @include('components.kpi-card', [
                'title' => 'Partenaires actifs',
                'icon' => 'store',
                'value' => number_format($shopifyData['activePartners'] ?? 0, 0, ',', ' '),
                'trendColor' => 'secondary',
                'trendIcon' => 'horizontal_rule',
                'trendText' => 'Stable',
                'dataKpi' => 'activePartners'
            ])
            
            @include('components.kpi-card', [
                'title' => 'Taux de validité',
                'icon' => 'check_circle',
                'value' => number_format($shopifyData['validationRate'] ?? 0, 1, ',', ' ') . '%',
                'trendColor' => 'error',
                'trendIcon' => 'trending_down',
                'trendText' => '-0.1% vs semaine dern.',
                'dataKpi' => 'validationRate'
            ])
        </div>
        
        <!-- Main Section Grid (Chart + Activity) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
            <!-- Interactive Chart (Section 2) -->
            <div class="lg:col-span-2 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col h-[500px]">
                @include('components.chart', [
                    'chartData' => $chartData ?? [
                        ['color' => 'primary', 'height' => 40],
                        ['color' => 'primary-fixed-dim', 'height' => 35],
                        ['color' => 'primary', 'height' => 60],
                        ['color' => 'primary-fixed-dim', 'height' => 55],
                        ['color' => 'primary', 'height' => 85],
                        ['color' => 'primary-fixed-dim', 'height' => 80],
                        ['color' => 'primary', 'height' => 70],
                        ['color' => 'primary-fixed-dim', 'height' => 65],
                        ['color' => 'primary', 'height' => 95],
                        ['color' => 'primary-fixed-dim', 'height' => 90],
                        ['color' => 'primary', 'height' => 50],
                        ['color' => 'primary-fixed-dim', 'height' => 45],
                    ]
                ])
            </div>
            
            <!-- Recent Activity Feed (Section 3) -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm flex flex-col h-[500px]">
                @include('components.activity-feed', [
                    'activities' => $recentActivities ?? [
                        [
                            'color' => 'primary',
                            'icon' => 'check_circle',
                            'title' => 'Scan Validé #QR-8821',
                            'description' => 'Partenaire: Boutique Centre-Ville',
                            'time' => 'Il y a 2 minutes'
                        ],
                        [
                            'color' => 'error',
                            'icon' => 'error',
                            'title' => 'Échec de validation #QR-9012',
                            'description' => 'Code expiré ou déjà utilisé',
                            'time' => 'Il y a 5 minutes'
                        ],
                        [
                            'color' => 'primary',
                            'icon' => 'check_circle',
                            'title' => 'Scan Validé #QR-8819',
                            'description' => 'Partenaire: Gare SNCF Lyon',
                            'time' => 'Il y a 12 minutes'
                        ],
                        [
                            'color' => 'tertiary',
                            'icon' => 'person_add',
                            'title' => 'Nouveau Partenaire',
                            'description' => 'Café de la Poste a rejoint le réseau',
                            'time' => 'Il y a 1 heure'
                        ],
                        [
                            'color' => 'primary',
                            'icon' => 'check_circle',
                            'title' => 'Scan Validé #QR-8815',
                            'description' => 'Partenaire: Cinéma Pathé',
                            'time' => 'Il y a 1 heure'
                        ],
                    ]
                ])
            </div>
        </div>
        
        <!-- Lower Section (Asymmetric / Bento Style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
            <!-- Help Card -->
            <div class="bg-primary text-white p-lg rounded-xl flex flex-col justify-between overflow-hidden relative group">
                <div class="relative z-10">
                    <h4 class="font-headline-md text-headline-md mb-xs">Besoin d'aide ?</h4>
                    <p class="text-on-primary-container text-body-md opacity-90">Accédez à notre base de connaissances ou contactez le support technique Shopify.</p>
                </div>
                
                <a href="{{ route('admin.support') }}" class="mt-lg relative z-10 bg-white text-primary px-lg py-sm rounded-lg font-bold w-fit hover:bg-opacity-90 transition-all active:scale-95">
                    Support 24/7
                </a>
                
                <span class="material-symbols-outlined absolute -bottom-8 -right-8 text-9xl opacity-10 rotate-12 group-hover:rotate-0 transition-transform duration-500">support_agent</span>
            </div>
            
            <!-- Partners Map -->
            <div class="lg:col-span-2 bg-surface-container-highest p-lg rounded-xl flex items-center gap-xl border border-outline-variant">
                <div class="hidden md:block w-32 h-32 bg-surface-container rounded-lg shrink-0 overflow-hidden">
                    <img class="w-full h-full object-cover" 
                         src="https://lh3.googleusercontent.com/aida-public/AB6AXuBuPtpoPlnrnv30FiUHSBNVDQoc8M0AaFnaOmdL9Psx8ZeM8iZiyKIrxwBfvMCtu1tFMRM6QxKkwG3YYP-TvWkKlA5dNDJJ9CXb-Kl3psscYSYbt3vugY0ZkeNRnNqJwdyFJ_7wrRbt52uRh-dHxRCSYHoQ003WeFha9S9YTQ-xjH-_rihuOZgoG0fXo-TZECnAlWd8G7cnhGDC5H7m7wgYrNCHav2q13kIvg-Fa374-uhUwXmSodOYyHWezlc6ID89Nqq2Gic8Ckcs"
                         alt="Carte des partenaires">
                </div>
                
                <div class="flex-1">
                    <h4 class="font-headline-md text-headline-md text-on-surface">Carte des Partenaires</h4>
                    <p class="text-secondary text-body-md mt-xs">Visualisez la répartition géographique de vos points de validation actifs en temps réel.</p>
                    
                    <div class="mt-lg flex gap-md">
                        <div class="text-center px-lg border-r border-outline-variant">
                            <p class="text-headline-md font-bold text-primary">{{ $shopifyData['partnersParis'] ?? 42 }}</p>
                            <p class="text-[10px] text-secondary uppercase font-bold tracking-widest">Paris</p>
                        </div>
                        
                        <div class="text-center px-lg border-r border-outline-variant">
                            <p class="text-headline-md font-bold text-primary">{{ $shopifyData['partnersLyon'] ?? 18 }}</p>
                            <p class="text-[10px] text-secondary uppercase font-bold tracking-widest">Lyon</p>
                        </div>
                        
                        <div class="text-center px-lg">
                            <p class="text-headline-md font-bold text-primary">{{ $shopifyData['partnersMarseille'] ?? 12 }}</p>
                            <p class="text-[10px] text-secondary uppercase font-bold tracking-widest">Marseille</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Mise à jour des données en temps réel
        function updateRealTimeData() {
            fetch('{{ route("admin.api.dashboard-data") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour les KPI
                        for (const [key, value] of Object.entries(data.data)) {
                            const elements = document.querySelectorAll(`[data-kpi="${key}"]`);
                            if (elements.length > 0) {
                                let displayValue = value;
                                
                                // Formater les nombres
                                if (typeof value === 'number') {
                                    if (key === 'validationRate') {
                                        displayValue = value.toFixed(1) + '%';
                                    } else {
                                        displayValue = new Intl.NumberFormat('fr-FR').format(value);
                                    }
                                }
                                
                                elements.forEach(el => {
                                    el.textContent = displayValue;
                                });
                            }
                        }
                        
                        // Mettre à jour les activités récentes si nécessaire
                        if (data.data.recentActivities && data.data.recentActivities.length > 0) {
                            // Code pour mettre à jour les activités récentes
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching real-time data:', error);
                });
        }
        
        // Mettre à jour les données toutes les 30 secondes
        setInterval(updateRealTimeData, 30000);
        
        // Première mise à jour après 5 secondes
        setTimeout(updateRealTimeData, 5000);
    </script>
@endpush