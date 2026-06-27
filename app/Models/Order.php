<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'shopify_order_id',
        'customer_email',
        'customer_name',
        'amount_cents',
        'currency',
        'status',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /**
     * QR Code actif courant (le plus récent non révoqué).
     */
    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)
            ->latest()
            ->whereNotIn('status', ['revoked']);
    }

    /**
     * Historique complet de tous les QR Codes (régénérations incluses).
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class)->latest();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePaid($query)
    {
        return $query->where('status', OrderStatus::Paid);
    }

    public function scopeWithoutQr($query)
    {
        return $query->whereDoesntHave('qrCodes');
    }

    /**
     * Recherche une commande par email client.
     *
     * ⚠️ Avec le cast 'encrypted', le WHERE direct ne fonctionne pas.
     * Cette méthode chiffre l'email recherché côté Laravel pour permettre
     * la comparaison exacte côté MySQL.
     */
    public function scopeWhereCustomerEmail($query, string $email)
    {
        return $query->where('customer_email', encrypt($email));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /**
     * Montant formaté en euros lisible.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_cents / 100, 2, ',', ' ').' '.$this->currency;
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'amount_cents' => 'integer',
            // Chiffrement au repos (RGPD). Le chiffrement utilise APP_KEY
            // de Laravel (AES-256-CBC). Le payload en base est illisible
            // sans la clé d'application, même avec accès direct à MySQL.
            'customer_email' => 'encrypted',
            'customer_name' => 'encrypted',
        ];
    }
}
