<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use App\Services\Support\OrderPayload;

/**
 * Contrat du service de traitement des webhooks Shopify.
 *
 * Étendu pour gérer les 7 topics de webhooks Shopify :
 *   - orders/create      → processOrderCreated
 *   - orders/paid        → processOrderPaid
 *   - orders/updated     → processOrderUpdated
 *   - orders/cancelled   → processOrderCancelled
 *   - orders/delete      → processOrderDeleted
 *   - refunds/create     → processRefundCreated
 *   - app/uninstalled    → processAppUninstalled
 *
 * Garantit l'idempotence :
 *  - UN même webhook N fois = 1 seule mutation d'Order
 *  - wasRecentlyCreated permet de déclencher le QR uniquement à la création
 */
interface ShopifyServiceInterface
{
    /**
     * Traite un webhook `orders/paid`.
     * Crée la commande et déclenche la génération QR.
     *
     * @param  array<string, mixed>  $payload  Payload Shopify brut
     */
    public function processOrderPaid(array $payload): Order;

    /**
     * Traite un webhook `orders/create`.
     * Crée la commande en statut pending. Pas de génération QR
     * (panier peut être abandonné).
     *
     * @param  array<string, mixed>  $payload
     */
    public function processOrderCreated(array $payload): Order;

    /**
     * Traite un webhook `orders/updated`.
     * Met à jour les informations modifiées (email, adresse, amount).
     * Si la commande passe à "paid" via update, déclenche le QR.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processOrderUpdated(array $payload): ?Order;

    /**
     * Traite un webhook `orders/cancelled`.
     * Annule l'Order et révoque le QR actif.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processOrderCancelled(array $payload): ?Order;

    /**
     * Traite un webhook `orders/delete`.
     * Supprime définitivement la commande (GDPR-like).
     * Le QR actif est révoqué en cascade.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processOrderDeleted(array $payload): bool;

    /**
     * Traite un webhook `refunds/create`.
     * Met à jour le statut de l'Order en conséquence et révoque le QR
     * actif (un QR validé contre une commande remboursée n'a plus de sens).
     *
     * @param  array<string, mixed>  $payload
     */
    public function processRefundCreated(array $payload): ?Order;

    /**
     * Traite un webhook `app/uninstalled`.
     * Log un événement critique + désactivation optionnelle de l'app.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processAppUninstalled(array $payload): void;

    /**
     * Construit un DTO OrderPayload depuis le payload Shopify brut.
     * Méthode publique pour permettre l'usage par les listeners et jobs.
     *
     * @param  array<string, mixed>  $payload
     */
    public function buildPayload(array $payload): OrderPayload;
}
