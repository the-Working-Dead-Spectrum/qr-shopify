<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ValidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    /** @use HasFactory<ValidationFactory> */
    use HasFactory;

    /**
     * Un scan est immuable — pas de updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'qr_code_id',
        'partner_id',
        'scanned_at',
        'status',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scanned_at', today());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
