<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Résultat métier d'une tentative de validation de QR Code.
 * Mapping direct avec les codes HTTP retournés par l'API.
 */
enum ValidationStatus: string
{
    case Valid = 'valid';
    case AlreadyUsed = 'already_used';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valide',
            self::AlreadyUsed => 'Déjà utilisé',
            self::Expired => 'Expiré',
            self::Revoked => 'Révoqué',
            self::Invalid => 'Invalide',
        };
    }
}
