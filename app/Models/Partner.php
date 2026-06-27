<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PartnerStatus;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'status',
        'api_calls_today',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    public function validatedQrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'partner_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', PartnerStatus::Active);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', PartnerStatus::Suspended);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === PartnerStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === PartnerStatus::Suspended;
    }

    public function incrementApiCalls(): void
    {
        $this->increment('api_calls_today');
    }

    // -------------------------------------------------------------------------
    // Boot — génération automatique du slug
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Partner $partner): void {
            if (empty($partner->slug)) {
                $partner->slug = Str::slug($partner->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'api_calls_today' => 'integer',
        ];
    }
}
