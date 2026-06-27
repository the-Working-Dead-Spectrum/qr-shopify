<?php

declare(strict_types=1);

namespace App\Enums;

enum QrStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            QrStatus::Active => 'Actif',
            QrStatus::Used => 'Utilisé',
            QrStatus::Expired => 'Expiré',
            QrStatus::Revoked => 'Révoqué',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            QrStatus::Active => false,
            QrStatus::Used,
            QrStatus::Expired,
            QrStatus::Revoked => true,
        };
    }
}
