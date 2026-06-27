<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lorsque la signature HMAC d'un webhook est invalide.
 *
 * Indique une tentative de spoofing ou un secret mal configuré.
 * Le middleware retourne systématiquement 401 dans ce cas.
 *
 * Aucune information sur le HMAC reçu ou le body n'est incluse pour
 * éviter de faciliter la recherche du secret.
 */
final class InvalidHmacException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid HMAC signature',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
