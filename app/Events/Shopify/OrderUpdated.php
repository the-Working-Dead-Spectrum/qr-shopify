<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand une commande est mise à jour (orders/updated).
 *
 * Différent de OrderImported : ici la commande EXISTE DÉJÀ,
 * seules ses informations ont changé (email, adresse, statut financier).
 *
 * Ne déclenche PAS de régénération QR sauf si le statut financier passe
 * à "paid" pour la première fois (géré par OrderPaid).
 */
final class OrderUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly array $changes = [],
    ) {}
}
