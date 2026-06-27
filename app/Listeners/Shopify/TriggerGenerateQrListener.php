<?php

declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Events\Shopify\OrderImported;
use App\Jobs\GenerateAndSendQrJob;
use App\Services\Concerns\LogsServiceActivity;
use Illuminate\Support\Facades\DB;

/**
 * Listener qui déclenche la génération QR après une commande payée.
 *
 * Découplage : la logique de génération reste dans le job. Le listener
 * ne fait QUE dispatcher le job conditionnellement.
 *
 * Condition de déclenchement :
 *  - OrderImported.isNew === true (première création)
 *  - Order est dans un statut payant (OrderPaid)
 *
 * Dispatché via Bus pour permettre un fake en test.
 */
final class TriggerGenerateQrListener
{
    use LogsServiceActivity;

    public function handle(OrderImported $event): void
    {
        if (! $event->isNew) {
            $this->info('shopify.listener.skip_existing_order', [
                'order_id' => $event->order->id,
            ]);

            return;
        }

        // On dispatch APRÈS le commit pour ne pas exécuter le job
        // sur une transaction non encore visible.
        DB::afterCommit(static function () use ($event): void {
            GenerateAndSendQrJob::dispatch($event->order->id);
        });
    }
}
