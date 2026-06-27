<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lors d'un échec d'appel à l'Admin API Shopify.
 *
 * Porte le contexte HTTP pour permettre au client de décider
 * s'il faut retry ou non :
 *  - 4xx → erreur client → pas de retry
 *  - 5xx → erreur serveur → retry exponentiel
 *  - 429 → rate limit → retry après Retry-After
 *
 * Le message ne doit JAMAIS contenir l'access_token ou des données
 * sensibles du payload.
 *
 * Note : non `final` pour permettre les sous-classes spécialisées
 * (ShopifyNetworkException, ShopifyTimeoutException) qui ajoutent du contexte.
 */
class ShopifyApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $endpoint = null,
        public readonly int $attempts = 1,
        /** @var array<string, mixed> */
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Indique si l'erreur peut être relancée (5xx, 429, réseau).
     */
    public function isRetryable(): bool
    {
        if ($this->statusCode === null) {
            return true; // Erreur réseau → toujours retryable.
        }

        if ($this->statusCode === 429) {
            return true; // Rate limit → toujours retryable.
        }

        return $this->statusCode >= 500 && $this->statusCode !== 501;
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    public function isClientError(): bool
    {
        return $this->statusCode !== null
            && $this->statusCode >= 400
            && $this->statusCode < 500;
    }
}
