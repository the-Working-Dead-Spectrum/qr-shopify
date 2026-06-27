<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Partner;
use App\Services\Support\DashboardKpis;

/**
 * Contrat du service de tableau de bord administrateur.
 *
 * Toutes les méthodes sont mises en cache avec TTL paramétrable.
 * La clé de cache est dérivée de la date courante (pour les KPIs quotidiens)
 * ou de l'identifiant de l'entité (pour les stats partenaire).
 */
interface DashboardServiceInterface
{
    /**
     * KPIs principaux pour le dashboard admin.
     * Cache TTL : 60 secondes (défaut, surchargeable).
     */
    public function kpis(): DashboardKpis;

    /**
     * Statistiques d'un partenaire sur les 7 derniers jours.
     * Cache invalidé après chaque validation par ce partenaire.
     */
    public function partnerStats(Partner $partner): array;

    /**
     * Invalide les caches dérivés d'une validation.
     * Appelé après chaque scan (succès ou échec).
     */
    public function invalidateAfterValidation(?int $partnerId = null): void;
}
