<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lorsqu'une requête webhook provient d'une IP non Shopify
 * alors que la vérification IP est activée en mode strict.
 *
 * Mode strict → 401 Unauthorized + log CRITICAL.
 * Mode permissif → log WARNING + requête acceptée (HMAC reste la sécurité).
 */
final class InvalidIpException extends RuntimeException
{
    public function __construct(
        public readonly string $clientIp = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Request from non-Shopify IP rejected [ip=%s]', $clientIp),
            0,
            $previous,
        );
    }
}
