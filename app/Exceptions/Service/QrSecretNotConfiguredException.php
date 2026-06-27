<?php

declare(strict_types=1);

namespace App\Exceptions\Service;

use RuntimeException;

/**
 * Levée quand APP_QR_SECRET est absent ou trop court.
 * Erreur de configuration serveur — ne devrait jamais arriver en prod.
 */
class QrSecretNotConfiguredException extends RuntimeException {}
