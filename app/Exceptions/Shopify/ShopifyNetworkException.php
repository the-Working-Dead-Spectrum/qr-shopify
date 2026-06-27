<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use Throwable;

/**
 * Exception levée lors d'une erreur réseau (DNS, connexion refusée, TLS).
 *
 * Indique un problème d'infrastructure et non une erreur Shopify.
 * Toujours retryable.
 */
final class ShopifyNetworkException extends ShopifyApiException
{
    public function __construct(
        string $message = 'Shopify API network error',
        public readonly ?string $endpoint = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            statusCode: null,
            endpoint: $endpoint,
            attempts: 1,
            previous: $previous,
        );
    }
}
