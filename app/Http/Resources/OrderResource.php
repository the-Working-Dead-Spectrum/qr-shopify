<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shopify_order_id' => $this->shopify_order_id,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'amount_cents' => $this->amount_cents,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relations eager-loaded
            'qr_code' => $this->whenLoaded('qrCode', fn () => new QrCodeResource($this->qrCode)),
            'qr_codes_history' => $this->whenLoaded('qrCodes', fn () => QrCodeResource::collection($this->qrCodes)),
        ];
    }
}
