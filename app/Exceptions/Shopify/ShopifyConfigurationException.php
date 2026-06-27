<?php

declare(strict_types=1);

namespace App\Exceptions\Shopify;

use RuntimeException;
use Throwable;

/**
 * Exception levée lorsque la configuration Shopify est incomplète ou invalide.
 *
 * Indique un problème côté serveur (variable d'env manquante, secret
 * trop court, etc.) — pas un problème Shopify.
 *
 * Cette exception est FATALE : elle doit déclencher une alerte
 * immédiate à l'administrateur (NotifyAdminOnErrorJob).
 */
final class ShopifyConfigurationException extends RuntimeException
{
    public function __construct(
        string $message = 'Shopify configuration error',
        public readonly string $missingKey = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                '%s [missing_key=%s]',
                $message,
                $missingKey,
            ),
            0,
            $previous,
        );
    }
}
