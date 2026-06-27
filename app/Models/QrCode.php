<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QrStatus;
use Carbon\Carbon;
use Database\Factories\QrCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    /** @use HasFactory<QrCodeFactory> */
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'uuid',
        'order_id',
        'status',
        'used_at',
        'expires_at',
        'partner_id',
        'regenerated_from',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Partenaire ayant effectué le scan de validation.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * QR Code dont celui-ci est la régénération.
     */
    public function regeneratedFrom(): BelongsTo
    {
        return $this->belongsTo(QrCode::class, 'regenerated_from');
    }

    /**
     * QR Codes régénérés depuis celui-ci.
     */
    public function regenerations(): HasMany
    {
        return $this->hasMany(QrCode::class, 'regenerated_from');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', QrStatus::Active)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpiredUnscanned($query)
    {
        return $query->where('status', QrStatus::Active)
            ->where('expires_at', '<', now());
    }

    public function scopeActivePeriod($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeUsedToday($query)
    {
        return $query->where('status', QrStatus::Used)
            ->whereDate('used_at', today());
    }

    // -------------------------------------------------------------------------
    // Méthodes métier — décrivent l'état du QR
    // -------------------------------------------------------------------------

    /**
     * QR déjà scanné et validé.
     */
    public function isUsed(): bool
    {
        return $this->status === QrStatus::Used;
    }

    /**
     * QR dont la date d'expiration est passée.
     * Indépendant du champ status — vérifie la date réelle.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * QR valide pour être scanné.
     * Actif ET non expiré.
     */
    public function isActive(): bool
    {
        return $this->status === QrStatus::Active && ! $this->isExpired();
    }

    /**
     * QR révoqué manuellement par un admin.
     */
    public function isRevoked(): bool
    {
        return $this->status === QrStatus::Revoked;
    }

    /**
     * Dans quel état le scan serait-il refusé ?
     * Retourne la raison de refus ou null si le QR est scannable.
     */
    public function refusalReason(): ?string
    {
        if ($this->isRevoked()) {
            return 'revoked';
        }

        if ($this->isUsed()) {
            return 'already_used';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return null;
    }

    /**
     * URL encodée dans le QR Code image.
     */
    public function getPublicUrl(): string
    {
        return route('qr.show', ['uuid' => $this->uuid]);
    }

    protected function casts(): array
    {
        return [
            'status' => QrStatus::class,
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
