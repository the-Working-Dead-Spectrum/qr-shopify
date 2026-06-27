<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * Immuable — jamais modifié après création.
     */
    public $timestamps = false;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'created_at',
    ];

    // -------------------------------------------------------------------------
    // Factory method — création d'une entrée de log
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        User $user,
        string $action,
        Model $subject,
        array $properties = [],
    ): self {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
