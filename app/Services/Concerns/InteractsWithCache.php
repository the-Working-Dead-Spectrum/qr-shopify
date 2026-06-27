<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Helpers de cache partagés entre Services.
 *
 * Centralise :
 *  - la résolution du repository (file vs redis) ;
 *  - le pattern `Cache::remember()` typé ;
 *  - l'invalidation par tag (préfixe hiérarchique).
 *
 * Pourquoi pas un trait statique : permet d'injecter un repository
 * custom en test (ex: array store), conformément aux bonnes pratiques Laravel.
 */
trait InteractsWithCache
{
    protected CacheRepository $cache;

    /**
     * Durée de cache par défaut (secondes). À surcharger par Service.
     */
    protected int $defaultTtl = 60;

    /**
     * Préfixe de cache (namespace des clés). À définir par Service.
     */
    protected string $cachePrefix = 'service';

    public function initializeCache(CacheRepository $cache): void
    {
        $this->cache = $cache;
    }

    protected function cache(): CacheRepository
    {
        return $this->cache ??= Cache::store();
    }

    /**
     * Récupère une valeur cachée ou exécute le callback pour la produire.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cache()->remember($this->cachePrefix.':'.$key, $ttl, $callback);
    }

    protected function forget(string $key): void
    {
        $this->cache()->forget($this->cachePrefix.':'.$key);
    }

    /**
     * Invalide toutes les clés matchant un préfixe.
     * Sur file/array driver : itère. Sur redis : utilise SCAN.
     */
    protected function flushPrefix(string $prefix): void
    {
        $fullPrefix = $this->cachePrefix.':'.$prefix;
        $store = $this->cache()->getStore();

        // Redis : purge efficace via SCAN + DEL
        if (method_exists($store, 'connection')) {
            $connection = $store->connection();
            $cursor = null;

            do {
                [$cursor, $keys] = $connection->scan($cursor, ['match' => $fullPrefix.'*', 'count' => 100]);

                if (! empty($keys)) {
                    $connection->del($keys);
                }
            } while ($cursor !== '0' && $cursor !== 0);

            return;
        }

        // Fallback générique : impossible sans scan, on log un warning.
        // En production avec redis, cette branche ne sera jamais empruntée.
        Log::warning(
            'flushPrefix utilisé sur un driver non-redis — inefficace',
            ['prefix' => $fullPrefix],
        );
    }
}
