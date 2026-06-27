<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contrat pour le service de dashboard Shopify.
 */
interface ShopifyDashboardServiceInterface
{
    /**
     * Récupère les données du dashboard depuis Shopify.
     */
    public function getDashboardData(): array;

    /**
     * Met à jour les données du dashboard en temps réel.
     */
    public function updateRealTimeData(): array;
}