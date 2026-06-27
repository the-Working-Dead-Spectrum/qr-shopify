<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            Role::Admin => 'Administrateur',
            Role::Partner => 'Partenaire',
        };
    }
}
