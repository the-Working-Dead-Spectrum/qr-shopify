<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lorsqu'un webhook déjà traité est rejoué.
 *
 * Shopify renvoie un X-Shopify-Webhook-Id unique par webhook. Si on
 * retrouve cet ID dans la table shopify_webhook_events, on rejette.
 *
 * Mapping HTTP recommandé : 409 Conflict.
 *
 * Ne pas confondre avec un simple duplicate légitime : on log en WARNING
 * car un replay excessif peut indiquer un problème côté Shopify.
 */
final class WebhookReplayException extends RuntimeException
{
    public function __construct(
        public readonly string $webhookId = '',
        public readonly string $topic = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Webhook replay detected [topic=%s, webhook_id=%s]',
                $topic,
                $webhookId,
            ),
            0,
            $previous,
        );
    }
}
