<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'un scan de validation.
 *
 * Champs sensibles masqués par défaut :
 *  - ip_address complète : exposée uniquement aux admins
 *  - user_agent complet : tronqué à 255 caractères pour éviter abus
 *
 * @mixin Validation
 */
final class ValidationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qr_code_id' => $this->qr_code_id,
            'partner_id' => $this->partner_id,
            'scanned_at' => $this->scanned_at?->toIso8601String(),
            'status' => $this->status,
            'is_valid' => $this->isValid(),

            // IP affichée partiellement (RGPD : pas d'IP complète côté front)
            'ip_address' => $this->maskIp($this->ip_address),

            // User agent tronqué (évite logs abusifs côté front)
            'user_agent' => $this->user_agent ? mb_substr((string) $this->user_agent, 0, 200) : null,

            'created_at' => $this->created_at?->toIso8601String(),

            // Relations
            'qr_code' => $this->whenLoaded('qrCode', fn () => new QrCodeResource($this->qrCode)),
            'partner' => $this->whenLoaded('partner', fn () => new PartnerResource($this->partner)),
        ];
    }

    /**
     * Masque partiellement une IPv4 ou IPv6.
     * Ex: 192.168.1.42 → 192.168.*.*   |  2001:db8::1 → 2001:db8::*
     */
    private function maskIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        // IPv6
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            $kept = array_slice($parts, 0, 4);

            return implode(':', $kept).':*';
        }

        // IPv4
        $parts = explode('.', $ip);

        if (count($parts) === 4) {
            $parts[2] = '*';
            $parts[3] = '*';

            return implode('.', $parts);
        }

        return '***';
    }
}
