<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ShopifyDashboardServiceInterface;
use App\Contracts\ShopifyServiceInterface;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Validation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service pour récupérer et traiter les données du dashboard depuis Shopify.
 */
class ShopifyDashboardService implements ShopifyDashboardServiceInterface
{
    public function __construct(
        private readonly ShopifyServiceInterface $shopifyService
    ) {}

    /**
     * Récupère les données du dashboard depuis Shopify.
     */
    public function getDashboardData(): array
    {
        try {
            // Récupérer les données depuis Shopify
            $shopifyData = $this->fetchDataFromShopify();
            
            // Récupérer les données locales
            $localData = $this->fetchLocalData();
            
            // Fusionner et formater les données
            return $this->mergeAndFormatData($shopifyData, $localData);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard data: ' . $e->getMessage());
            
            // Retourner des données par défaut en cas d'erreur
            return $this->getDefaultData();
        }
    }

    /**
     * Récupère les données depuis l'API Shopify.
     */
    private function fetchDataFromShopify(): array
    {
        // Dans une implémentation réelle, cela appellerait l'API Shopify
        // Pour l'instant, on utilise des données mockées
        return [
            'totalSales' => $this->getTotalSalesFromShopify(),
            'ordersCount' => $this->getOrdersCountFromShopify(),
            'recentOrders' => $this->getRecentOrdersFromShopify(),
        ];
    }

    /**
     * Récupère les données locales depuis la base de données.
     */
    private function fetchLocalData(): array
    {
        return [
            'scansToday' => $this->getScansToday(),
            'activePartners' => $this->getActivePartners(),
            'validationRate' => $this->getValidationRate(),
            'partnersByCity' => $this->getPartnersByCity(),
            'recentActivities' => $this->getRecentActivities(),
            'chartData' => $this->getChartData(),
        ];
    }

    /**
     * Fusionne et formate les données.
     */
    private function mergeAndFormatData(array $shopifyData, array $localData): array
    {
        return [
            'totalSales' => $shopifyData['totalSales'] ?? 0,
            'scansToday' => $localData['scansToday'] ?? 0,
            'activePartners' => $localData['activePartners'] ?? 0,
            'validationRate' => $localData['validationRate'] ?? 0,
            'partnersParis' => $localData['partnersByCity']['Paris'] ?? 0,
            'partnersLyon' => $localData['partnersByCity']['Lyon'] ?? 0,
            'partnersMarseille' => $localData['partnersByCity']['Marseille'] ?? 0,
            'recentActivities' => $localData['recentActivities'] ?? [],
            'chartData' => $localData['chartData'] ?? [],
        ];
    }

    /**
     * Retourne des données par défaut en cas d'erreur.
     */
    private function getDefaultData(): array
    {
        return [
            'totalSales' => 0,
            'scansToday' => 0,
            'activePartners' => 0,
            'validationRate' => 0,
            'partnersParis' => 0,
            'partnersLyon' => 0,
            'partnersMarseille' => 0,
            'recentActivities' => [],
            'chartData' => [],
        ];
    }

    // -------------------------------------------------------------------------
    // Méthodes pour récupérer les données depuis Shopify
    // -------------------------------------------------------------------------

    private function getTotalSalesFromShopify(): float
    {
        // Dans une implémentation réelle, cela appellerait l'API Shopify
        // pour récupérer le total des ventes
        return 124590.00;
    }

    private function getOrdersCountFromShopify(): int
    {
        // Dans une implémentation réelle, cela appellerait l'API Shopify
        // pour récupérer le nombre de commandes
        return Order::count();
    }

    private function getRecentOrdersFromShopify(): array
    {
        // Dans une implémentation réelle, cela appellerait l'API Shopify
        // pour récupérer les commandes récentes
        return Order::with('qrCode')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Méthodes pour récupérer les données locales
    // -------------------------------------------------------------------------

    private function getScansToday(): int
    {
        return Validation::whereDate('created_at', Carbon::today())
            ->count();
    }

    private function getActivePartners(): int
    {
        return Partner::where('status', 'active')->count();
    }

    private function getValidationRate(): float
    {
        $totalValidations = Validation::count();
        $successfulValidations = Validation::where('status', 'success')->count();
        
        return $totalValidations > 0 ? round(($successfulValidations / $totalValidations) * 100, 1) : 0;
    }

    private function getPartnersByCity(): array
    {
        return [
            'Paris' => Partner::where('city', 'Paris')->count(),
            'Lyon' => Partner::where('city', 'Lyon')->count(),
            'Marseille' => Partner::where('city', 'Marseille')->count(),
        ];
    }

    private function getRecentActivities(): array
    {
        // Dans une implémentation réelle, cela récupérerait les activités récentes
        // depuis la base de données ou les logs
        return [
            [
                'color' => 'primary',
                'icon' => 'check_circle',
                'title' => 'Scan Validé #QR-' . rand(1000, 9999),
                'description' => 'Partenaire: Boutique Centre-Ville',
                'time' => 'Il y a ' . rand(1, 30) . ' minutes'
            ],
            [
                'color' => 'error',
                'icon' => 'error',
                'title' => 'Échec de validation #QR-' . rand(1000, 9999),
                'description' => 'Code expiré ou déjà utilisé',
                'time' => 'Il y a ' . rand(1, 10) . ' minutes'
            ],
            [
                'color' => 'primary',
                'icon' => 'check_circle',
                'title' => 'Scan Validé #QR-' . rand(1000, 9999),
                'description' => 'Partenaire: Gare SNCF Lyon',
                'time' => 'Il y a ' . rand(30, 60) . ' minutes'
            ],
            [
                'color' => 'tertiary',
                'icon' => 'person_add',
                'title' => 'Nouveau Partenaire',
                'description' => 'Café de la Poste a rejoint le réseau',
                'time' => 'Il y a ' . rand(1, 3) . ' heure(s)'
            ],
            [
                'color' => 'primary',
                'icon' => 'check_circle',
                'title' => 'Scan Validé #QR-' . rand(1000, 9999),
                'description' => 'Partenaire: Cinéma Pathé',
                'time' => 'Il y a ' . rand(1, 3) . ' heure(s)'
            ],
        ];
    }

    private function getChartData(): array
    {
        // Dans une implémentation réelle, cela générerait des données pour le graphique
        // basé sur les données historiques
        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $data[] = [
                'color' => $i % 2 === 0 ? 'primary' : 'primary-fixed-dim',
                'height' => rand(30, 95)
            ];
        }
        return $data;
    }

    /**
     * Met à jour les données du dashboard en temps réel.
     */
    public function updateRealTimeData(): array
    {
        return [
            'scansToday' => $this->getScansToday(),
            'validationRate' => $this->getValidationRate(),
            'recentActivities' => $this->getRecentActivities(),
        ];
    }
}