<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Partner;
use App\Services\Support\ValidationResult;

/**
 * Contrat du service de validation d'un QR Code scanné.
 *
 * Implémentation OBLIGATOIREMENT transactionnelle avec verrou pessimiste :
 * deux scans concurrents sur le même UUID ne peuvent jamais retourner 'valid'
 * tous les deux. Le contrat reflète cet invariant.
 */
interface ValidationServiceInterface
{
    /**
     * Valide un QR Code scanné par un partenaire.
     *
     * Le résultat encapsule : statut HTTP, raison métier, message utilisateur,
     * et un snapshot des données pertinentes (order_id, expires_at, used_at).
     *
     * Codes de retour (alignés sur SPECS §6.3) :
     *  - valid        → HTTP 200
     *  - already_used → HTTP 409
     *  - expired      → HTTP 410
     *  - invalid      → HTTP 404 (UUID inconnu)
     *  - revoked      → HTTP 403
     */
    public function validate(string $uuid, Partner $partner, ?string $ip = null, ?string $userAgent = null): ValidationResult;
}
