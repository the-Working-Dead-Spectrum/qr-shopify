<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Service de gestion de la rotation des secrets Shopify.
 *
 * Stratégie de double secret (graceful rotation) :
 *  - Pendant la rotation, on conserve l'ancien ET le nouveau secret.
 *  - Les webhooks entrants sont validés contre les DEUX secrets.
 *  - Une fois la fenêtre de grâce écoulée, l'ancien est supprimé.
 *
 * Cette stratégie est essentielle car Shopify n'a aucun mécanisme
 * "atomic secret swap" : il y a toujours un délai entre le moment où
 * on configure le nouveau secret dans Shopify et celui où l'ancien
 * est retiré. Pendant ce délai, on DOIT accepter les deux.
 *
 * Le fichier de rotation est chiffré au repos (Laravel Crypt) et
 * stocké hors du répertoire Git (storage/app/private/...).
 */
final class ShopifySecretRotationService
{
    private const STORAGE_DISK = 'local';

    private const STORAGE_PATH = 'shopify/secret-rotation.json';

    /**
     * Durée de grâce (en heures) pendant laquelle l'ancien secret reste valide.
     * Pendant ce laps, le middleware HMAC accepte les deux secrets.
     */
    public const GRACE_PERIOD_HOURS = 24;

    /**
     * Récupère le secret actif (le plus récent).
     *
     * Si une rotation est en cours et que le nouveau secret est dans sa
     * fenêtre de grâce, on retourne le nouveau.
     *
     * @throws RuntimeException Si aucun secret n'est configuré
     */
    public function getActiveSecret(): string
    {
        $state = $this->loadState();

        if ($state === null) {
            $fallback = (string) config('shopify.webhook_secret');

            if ($fallback === '') {
                throw new RuntimeException(
                    'Aucun secret Shopify configuré. Définir SHOPIFY_WEBHOOK_SECRET dans .env.',
                );
            }

            return $fallback;
        }

        // Si on a un "pending" et qu'on est dans la fenêtre de grâce
        if (isset($state['pending']) && $this->isInGracePeriod($state)) {
            return $state['pending']['secret'];
        }

        return $state['current']['secret'];
    }

    /**
     * Récupère tous les secrets valides (courant + ancien pendant la grâce).
     * Utilisé par le middleware HMAC pour accepter les deux pendant la rotation.
     *
     * Tolérance : si le storage est indisponible, on fallback sur la
     * config (comportement legacy). Le fichier rotation n'est qu'une
     * amélioration, jamais un point de défaillance unique.
     *
     * @return array<int, string>
     */
    public function getAllValidSecrets(): array
    {
        try {
            $state = $this->loadState();
        } catch (Throwable $e) {
            // Storage indisponible (test, FS en lecture seule) → fallback
            Log::warning('[shopify.rotation] state_load_failed_fallback', [
                'error' => $e->getMessage(),
            ]);
            $state = null;
        }

        if ($state === null) {
            $fallback = (string) config('shopify.webhook_secret');

            return $fallback === '' ? [] : [$fallback];
        }

        $secrets = [$state['current']['secret']];

        if (isset($state['pending']) && $this->isInGracePeriod($state)) {
            $secrets[] = $state['pending']['secret'];
        }

        return $secrets;
    }

    /**
     * Démarre une rotation : enregistre un nouveau secret en parallèle
     * du secret courant.
     *
     * @param  string  $reason  Raison de la rotation (audit log)
     */
    public function startRotation(string $newSecret, string $reason = 'manual'): void
    {
        $now = now()->toIso8601String();

        $current = [
            'secret' => $this->getActiveSecret(),
            'rotated_at' => $now,
        ];

        $state = [
            'current' => $current,
            'pending' => [
                'secret' => $newSecret,
                'rotated_at' => $now,
                'expires_at' => now()->addHours(self::GRACE_PERIOD_HOURS)->toIso8601String(),
                'reason' => $reason,
            ],
            'history' => $this->loadHistory($this->loadState()),
        ];

        $this->persistState($state);

        Log::info('[shopify.rotation] started', [
            'reason' => $reason,
            'grace_expires_at' => $state['pending']['expires_at'],
        ]);
    }

    /**
     * Finalise la rotation : le pending devient le current, et l'ancien est archivé.
     */
    public function finalizeRotation(): bool
    {
        $state = $this->loadState();

        if ($state === null || ! isset($state['pending'])) {
            return false;
        }

        $history = $this->loadHistory($state);
        $history[] = [
            'secret' => $state['current']['secret'],
            'rotated_at' => $state['current']['rotated_at'],
            'archived_at' => now()->toIso8601String(),
        ];

        $newState = [
            'current' => [
                'secret' => $state['pending']['secret'],
                'rotated_at' => now()->toIso8601String(),
            ],
            'pending' => null,
            'history' => $history,
        ];

        $this->persistState($newState);

        Log::info('[shopify.rotation] finalized', [
            'history_count' => count($history),
        ]);

        return true;
    }

    /**
     * Annule une rotation en cours.
     */
    public function cancelRotation(): bool
    {
        $state = $this->loadState();

        if ($state === null || ! isset($state['pending'])) {
            return false;
        }

        $newState = [
            'current' => $state['current'],
            'pending' => null,
            'history' => $state['history'] ?? [],
        ];

        $this->persistState($newState);

        Log::warning('[shopify.rotation] cancelled', [
            'reason' => 'manual_cancellation',
        ]);

        return true;
    }

    /**
     * Retourne l'état actuel de la rotation (pour affichage dashboard).
     *
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        $state = $this->loadState();

        if ($state === null) {
            return [
                'status' => 'no_state',
                'current_set_at' => null,
                'pending' => null,
            ];
        }

        return [
            'status' => isset($state['pending']) ? 'rotating' : 'stable',
            'current_set_at' => $state['current']['rotated_at'] ?? null,
            'pending' => $state['pending'] ?? null,
            'history_count' => count($state['history'] ?? []),
        ];
    }

    /**
     * Nettoie l'historique de rotation au-delà de N entrées.
     */
    public function pruneHistory(int $keepLast = 10): int
    {
        $state = $this->loadState();

        if ($state === null || ! isset($state['history'])) {
            return 0;
        }

        $originalCount = count($state['history']);

        if ($originalCount <= $keepLast) {
            return 0;
        }

        $state['history'] = array_slice($state['history'], -$keepLast);
        $this->persistState($state);

        $removed = $originalCount - $keepLast;
        Log::info('[shopify.rotation] history_pruned', ['removed' => $removed, 'kept' => $keepLast]);

        return $removed;
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    private function loadState(): ?array
    {
        if (! Storage::disk(self::STORAGE_DISK)->exists(self::STORAGE_PATH)) {
            return null;
        }

        try {
            $encrypted = Storage::disk(self::STORAGE_DISK)->get(self::STORAGE_PATH);
            $decoded = Crypt::decryptString($encrypted);
            $state = json_decode($decoded, true);

            return is_array($state) ? $state : null;
        } catch (Throwable $e) {
            Log::error('[shopify.rotation] state_load_failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function persistState(array $state): void
    {
        $encoded = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $encrypted = Crypt::encryptString($encoded);

        Storage::disk(self::STORAGE_DISK)->put(self::STORAGE_PATH, $encrypted);
    }

    /**
     * @param  array<string, mixed>|null  $state
     * @return array<int, array<string, mixed>>
     */
    private function loadHistory(?array $state): array
    {
        if ($state === null || ! isset($state['history']) || ! is_array($state['history'])) {
            return [];
        }

        return $state['history'];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function isInGracePeriod(array $state): bool
    {
        if (! isset($state['pending']['expires_at'])) {
            return false;
        }

        return now()->lessThan(CarbonImmutable::parse($state['pending']['expires_at']));
    }
}
