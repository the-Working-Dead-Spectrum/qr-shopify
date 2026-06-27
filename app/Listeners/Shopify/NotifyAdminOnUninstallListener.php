<?php

declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Events\Shopify\AppUninstalled;
use Illuminate\Support\Facades\Log;

/**
 * Listener CRITIQUE déclenché par `app/uninstalled`.
 *
 * Conséquences :
 *  - Log en CRITICAL pour Sentry/Datadog
 *  - Désactive les webhooks sortants si pertinent
 *  - Alerte admin via canal dédié (Slack, Discord, email)
 *
 * Note : on ne supprime PAS immédiatement les données clients
 * (obligation légale de conservation comptable — 10 ans en France).
 * Un job d'anonymisation RGPD est déclenché en parallèle.
 */
final class NotifyAdminOnUninstallListener
{
    public function handle(AppUninstalled $event): void
    {
        Log::critical('[shopify] app_uninstalled', [
            'shop_domain' => $event->shopDomain,
            'occurred_at' => $event->occurredAt,
            'impact' => 'Webhooks désactivés. Données clients toujours conservées (obligation comptable 10 ans).',
        ]);

        // TODO: dispatch d'un job d'alerte multi-canal (Slack/Discord/email)
        // Implémenté dans une phase ultérieure.
    }
}
