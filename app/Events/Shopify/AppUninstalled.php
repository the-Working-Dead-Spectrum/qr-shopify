<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement CRITIQUE émis quand l'application est désinstallée du shop Shopify.
 *
 * Conséquences possibles :
 *  - Désactivation des webhooks (on ne recevra plus rien)
 *  - Anonymisation des données (RGPD)
 *  - Alerte admin immédiate
 *
 * Cf. SPECS §14 RGPD — conservation des données après désinstallation.
 */
final class AppUninstalled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $shopDomain,
        public readonly ?string $occurredAt = null,
    ) {}
}
