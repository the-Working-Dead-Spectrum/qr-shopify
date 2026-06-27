<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'un Partner.
 *
 * ⚠️ Ne jamais exposer le `user.password` ou les tokens Sanctum.
 *
 * @mixin Partner
 */
final class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->isActive(),
            'api_calls_today' => $this->api_calls_today,
            'created_at' => $this->created_at?->toIso8601String(),

            // Email utilisateur associé (utile pour l'admin, jamais pour les partenaires)
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
        ];
    }
}
