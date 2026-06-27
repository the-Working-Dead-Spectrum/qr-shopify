<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Contracts\ShopifyClientInterface;
use App\Exceptions\Shopify\ShopifyApiException;
use App\Exceptions\Shopify\ShopifyConfigurationException;
use App\Exceptions\Shopify\ShopifyNetworkException;
use App\Exceptions\Shopify\ShopifyTimeoutException;
use App\Services\Concerns\LogsServiceActivity;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Client HTTP sortant pour Shopify Admin API.
 *
 * Responsabilités :
 *  - Authentifier via header X-Shopify-Access-Token
 *  - Ajouter timeouts (connect + total)
 *  - Implémenter retry exponentiel (5xx, 429, réseau)
 *  - Logger tous les appels (latence, status, endpoint)
 *  - Convertir les exceptions Guzzle/Laravel en exceptions Shopify
 *
 * Ne JAMAIS appeler directement depuis un Controller : passer par les
 * Services métier (ShopifyService, ProductService, etc.).
 */
final class ShopifyClient implements ShopifyClientInterface
{
    use LogsServiceActivity;

    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    // -------------------------------------------------------------------------
    // Implémentation centrale
    // -------------------------------------------------------------------------

    /**
     * Exécute une requête avec retry exponentiel + logging.
     *
     * @param  array<string, mixed>  $options  Options Laravel HTTP client
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        $url = $this->buildUrl($endpoint);
        $accessToken = $this->requireAccessToken();

        $maxAttempts = (int) config('shopify.retry.max_attempts', 5);
        $initialDelay = (int) config('shopify.retry.initial_delay', 500);
        $multiplier = (float) config('shopify.retry.multiplier', 2.0);
        $maxDelay = (int) config('shopify.retry.max_delay', 30000);

        $attempt = 0;
        $delayMs = $initialDelay;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $start = microtime(true);

            try {
                $response = $this->http
                    ->withToken($accessToken)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-Shopify-Client' => sprintf('qr-shopify/%s', config('app.version', '1.0.0')),
                    ])
                    ->connectTimeout((int) config('shopify.connect_timeout', 5))
                    ->timeout((int) config('shopify.timeout', 30))
                    ->send($method, $url, $options);

                $latencyMs = (int) ((microtime(true) - $start) * 1000);

                $this->logRequest($method, $endpoint, $response->status(), $latencyMs, $attempt);

                // Cas succès
                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                // Cas erreur : décider si retry
                $statusCode = $response->status();

                if ($statusCode === 429) {
                    // Rate limit : on respecte Retry-After si présent
                    $retryAfter = (int) ($response->header('Retry-After') ?? 0);

                    if ($retryAfter > 0) {
                        $delayMs = min($retryAfter * 1000, $maxDelay);
                    }
                } elseif (! $this->isRetryableStatus($statusCode)) {
                    // Erreur client → pas de retry, on throw directement
                    throw $this->buildApiException($response, $endpoint, $attempt);
                }

                $lastException = $this->buildApiException($response, $endpoint, $attempt);

            } catch (ConnectionException $e) {
                $latencyMs = (int) ((microtime(true) - $start) * 1000);
                $this->logRequest($method, $endpoint, 0, $latencyMs, $attempt, $e->getMessage());

                // Timeout DNS / connexion : retryable
                $lastException = new ShopifyNetworkException(
                    message: $e->getMessage(),
                    endpoint: $endpoint,
                    previous: $e,
                );

                // Si le timeout de connexion a expiré → exception dédiée
                if (str_contains(strtolower($e->getMessage()), 'timed out')) {
                    $lastException = new ShopifyTimeoutException(
                        message: 'Shopify API connection timeout',
                        timeoutSeconds: (int) config('shopify.connect_timeout', 5),
                        endpoint: $endpoint,
                        previous: $e,
                    );
                }
            } catch (RequestException $e) {
                $latencyMs = (int) ((microtime(true) - $start) * 1000);
                $this->logRequest($method, $endpoint, $e->response->status() ?? 0, $latencyMs, $attempt, $e->getMessage());

                throw $this->buildApiException($e->response, $endpoint, $attempt, $e);
            } catch (Throwable $e) {
                // Erreur inattendue — on remonte
                $this->logRequest($method, $endpoint, 0, 0, $attempt, $e->getMessage());

                throw new ShopifyApiException(
                    message: 'Unexpected error during Shopify API call: '.$e->getMessage(),
                    endpoint: $endpoint,
                    attempts: $attempt,
                    previous: $e,
                );
            }

            // Si on a encore des tentatives, on attend
            if ($attempt < $maxAttempts) {
                $this->wait($delayMs);
                $delayMs = (int) min($delayMs * $multiplier, $maxDelay);
            }
        }

        // Échec définitif
        if ($lastException instanceof ShopifyApiException) {
            throw $lastException;
        }

        throw new ShopifyApiException(
            message: 'Shopify API request failed after '.$maxAttempts.' attempts',
            endpoint: $endpoint,
            attempts: $attempt,
            previous: $lastException,
        );
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function buildUrl(string $endpoint): string
    {
        $base = rtrim((string) config('shopify.api_base_url'), '/');

        if ($base === '') {
            throw new ShopifyConfigurationException(
                'SHOPIFY_API_BASE_URL non configuré',
                'shopify.api_base_url',
            );
        }

        // L'endpoint peut déjà commencer par "/" → on normalise.
        $path = ltrim($endpoint, '/');

        return $base.'/'.$path;
    }

    private function requireAccessToken(): string
    {
        $token = (string) config('shopify.access_token');

        if ($token === '') {
            throw new ShopifyConfigurationException(
                'SHOPIFY_ACCESS_TOKEN non configuré — impossible d\'appeler l\'Admin API',
                'shopify.access_token',
            );
        }

        return $token;
    }

    private function isRetryableStatus(int $status): bool
    {
        if ($status === 429) {
            return true;
        }

        // 5xx (sauf 501 Not Implemented)
        return $status >= 500 && $status !== 501;
    }

    private function buildApiException(
        Response $response,
        string $endpoint,
        int $attempt,
        ?Throwable $previous = null,
    ): ShopifyApiException {
        $status = $response->status();
        $body = $this->sanitizeResponseBody($response->json());

        return new ShopifyApiException(
            message: sprintf('Shopify API error %d on %s', $status, $endpoint),
            statusCode: $status,
            endpoint: $endpoint,
            attempts: $attempt,
            context: $body,
            previous: $previous,
        );
    }

    /**
     * Nettoie le body de réponse pour exclure les données sensibles.
     *
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function sanitizeResponseBody(?array $body): array
    {
        if ($body === null) {
            return [];
        }

        // Supprime les champs sensibles connus
        unset(
            $body['customer']['email'],
            $body['customer']['phone'],
            $body['email'],
            $body['contact_email'],
        );

        return $body;
    }

    private function wait(int $delayMs): void
    {
        usleep($delayMs * 1000);
    }

    /**
     * Log structuré d'un appel API.
     */
    private function logRequest(
        string $method,
        string $endpoint,
        int $status,
        int $latencyMs,
        int $attempt,
        ?string $error = null,
    ): void {
        $level = match (true) {
            $status >= 500, $error !== null => 'error',
            $status >= 400 => 'warning',
            $attempt > 1 => 'info',
            default => 'debug',
        };

        $event = sprintf('shopify.api.%s', strtolower($method));

        $context = [
            'endpoint' => $endpoint,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'attempt' => $attempt,
        ];

        if ($error !== null) {
            $context['error'] = $error;
        }

        match ($level) {
            'error' => $this->error($event, $context),
            'warning' => $this->warning($event, $context),
            'info' => $this->info($event, $context),
            default => $this->info($event, $context),
        };
    }
}
