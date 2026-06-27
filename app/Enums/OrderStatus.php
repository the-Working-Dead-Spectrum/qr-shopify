<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            OrderStatus::Pending => 'En attente',
            OrderStatus::Paid => 'Payée',
            OrderStatus::Cancelled => 'Annulée',
        };
    }
}
