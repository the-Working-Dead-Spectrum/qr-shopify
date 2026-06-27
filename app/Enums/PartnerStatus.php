<?php

declare(strict_types=1);

namespace App\Enums;

enum PartnerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            PartnerStatus::Active => 'Actif',
            PartnerStatus::Inactive => 'Inactif',
            PartnerStatus::Suspended => 'Suspendu',
        };
    }

    public function isOperational(): bool
    {
        return $this === PartnerStatus::Active;
    }
}
