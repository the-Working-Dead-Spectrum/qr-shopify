<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use App\Models\QrCode;

/**
 * Contrat du générateur de QR Codes.
 *
 * Toutes les opérations cryptographiques et de rendu d'image
 * passent par cette interface. Permet de substituer l'implémentation
 * (ex: autre bibliothèque, mock en test) sans toucher au métier.
 */
interface QrCodeGeneratorInterface
{
    /**
     * Génère un QrCode signé pour une commande.
     *
     * Doit être idempotent au sens : (1) deux appels produisent deux QR distincts
     * (UUID v4 + entropie), (2) l'UUID stocké est le HMAC, jamais le brut.
     *
     * @param  QrCode|null  $regenerateFrom  Si fourni, marque l'ancien QR comme révoqué
     *                                       et crée un nouveau lié par regenerated_from.
     */
    public function generate(Order $order, ?QrCode $regenerateFrom = null): QrCode;

    /**
     * Génère l'image PNG du QR Code en base64.
     *
     * Le contenu encodé dans le QR est l'URL publique de validation
     * (route 'qr.show', uuid). Le rendu est optimisé pour email :
     * haute correction d'erreur, taille unique PNG.
     */
    public function generateImage(string $uuid): string;

    /**
     * Signe un UUID brut avec le secret HMAC applicatif.
     * Hash SHA-256 hexadécimal (64 caractères).
     */
    public function signUuid(string $uuid): string;
}
