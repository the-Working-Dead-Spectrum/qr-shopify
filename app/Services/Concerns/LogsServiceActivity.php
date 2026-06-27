<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Logging métier uniforme pour les Services.
 *
 * Tous les Services loggent :
 *  - les événements métier réussis (INFO)
 *  - les refus / échecs métier (WARNING)
 *  - les erreurs techniques (ERROR)
 *
 * Le format est JSON structuré pour exploitation par Sentry / Datadog / ELK.
 * Aucun secret / token / UUID complet n'est jamais loggé (cf. SPECS §16.2).
 */
trait LogsServiceActivity
{
    protected ?LoggerInterface $logger = null;

    protected function logger(): LoggerInterface
    {
        return $this->logger ??= Log::channel();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function info(string $event, array $context = []): void
    {
        $this->logger()->info("[service] {$event}", $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function warning(string $event, array $context = []): void
    {
        $this->logger()->warning("[service] {$event}", $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function error(string $event, array $context = []): void
    {
        $this->logger()->error("[service] {$event}", $this->sanitize($context));
    }

    /**
     * Retire les données sensibles avant logging (cf. SPECS §16.2).
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function sanitize(array $context): array
    {
        unset(
            $context['token'],
            $context['secret'],
            $context['password'],
            $context['authorization'],
            $context['uuid_full'],
        );

        return $context;
    }
}
