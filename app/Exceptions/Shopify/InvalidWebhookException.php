<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lorsqu'un webhook Shopify est invalide :
 *  - topic non supporté
 *  - payload mal formé (JSON corrompu, champs manquants)
 *  - entête manquante (X-Shopify-Topic, X-Shopify-Webhook-Id)
 *
 * Mapping HTTP recommandé : 422 Unprocessable Entity.
 *
 * Cette exception NE DOIT PAS déclencher de retry Shopify : le payload est
 * définitivement inutilisable.
 */
final class InvalidWebhookException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid Shopify webhook payload',
        /** @var array<string, mixed> */
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
