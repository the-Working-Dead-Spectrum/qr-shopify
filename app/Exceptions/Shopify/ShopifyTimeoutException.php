<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use Throwable;

/**
 * Exception levée lorsqu'un appel Admin API Shopify dépasse le timeout.
 *
 * Sous-classe de ShopifyApiException avec retry = true.
 * Le délai du retry suivant est doublé (backoff exponentiel).
 */
final class ShopifyTimeoutException extends ShopifyApiException
{
    public function __construct(
        string $message = 'Shopify API timeout',
        public readonly int $timeoutSeconds = 0,
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
