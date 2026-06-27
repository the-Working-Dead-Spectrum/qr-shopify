<?php

declare(strict_types=1);

namespace App\Exceptions\Service;

use RuntimeException;

/**
 * Levée quand un payload (Shopify, API, ...) ne contient pas les champs requis.
 * Le Controller la traduit en HTTP 422 Unprocessable Entity.
 */
class InvalidPayloadException extends RuntimeException
{
    public static function missing(string $field): self
    {
        return new self("Champ requis manquant : {$field}");
    }
}
