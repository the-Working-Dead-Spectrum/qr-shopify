<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'un QR Code.
 *
 * Découple le modèle interne de la sortie API. Permet :
 *  - d'évoluer le modèle sans casser l'API
 *  - de filtrer les champs sensibles
 *  - d'ajouter des champs calculés (status_label, is_scannable)
 *
 * @mixin QrCode
 */
final class QrCodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_used' => $this->isUsed(),
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'is_revoked' => $this->isRevoked(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'used_at' => $this->used_at?->toIso8601String(),
            'order_id' => $this->order_id,
            'partner_id' => $this->partner_id,
            'regenerated_from' => $this->regenerated_from,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Champs calculés
            'public_url' => $this->getPublicUrl(),

            // Relations eager-loaded : null si pas chargées
            'order' => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
            'partner' => $this->whenLoaded('partner', fn () => new PartnerResource($this->partner)),
        ];
    }
}
