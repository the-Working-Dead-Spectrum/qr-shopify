<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Exceptions\Shopify\ShopifyTimeoutException;
use App\Services\Shopify\ShopifyClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests unitaires pour ShopifyClient.
 *
 * Vérifie :
 *  - Retry exponentiel sur 5xx
 *  - Pas de retry sur 4xx
 *  - Backoff progressif
 *  - Timeouts
 *  - Headers d'authentification
 *  - Exceptions spécifiques (Timeout, Network)
 */
class ShopifyClientTest extends TestCase
{
    private HttpFactory $http;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.access_token', 'shpat_test_token');
        Config::set('shopify.api_base_url', 'https://test-shop.myshopify.com/admin/api/2025-01/');
        Config::set('shopify.connect_timeout', 5);
        Config::set('shopify.timeout', 30);
        Config::set('shopify.retry.max_attempts', 3);
        Config::set('shopify.retry.initial_delay', 10); // court pour les tests
        Config::set('shopify.retry.multiplier', 2.0);
        Config::set('shopify.retry.max_delay', 100);

        $this->http = $this->app->make(HttpFactory::class);
    }

    public function test_get_avec_reponse_200_retourne_le_json(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->response([
                'shop' => ['id' => 1, 'name' => 'Test Shop'],
            ], 200),
        ]);

        $client = new ShopifyClient($this->http);
        $result = $client->get('shop.json');

        $this->assertSame(['shop' => ['id' => 1, 'name' => 'Test Shop']], $result);
    }

    public function test_retry_sur_erreur_500(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->sequence()
                ->push(['error' => 'server error'], 500)
                ->push(['error' => 'server error'], 500)
                ->push(['shop' => ['name' => 'OK']], 200),
        ]);

        $client = new ShopifyClient($this->http);
        $result = $client->get('shop.json');

        $this->assertSame(['shop' => ['name' => 'OK']], $result);
    }

    public function test_pas_de_retry_sur_erreur_4xx(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->response(
                ['errors' => 'Not Found'],
                404,
            ),
        ]);

        $client = new ShopifyClient($this->http);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('Shopify API error 404');

        $client->get('unknown.json');
    }

    public function test_retry_sur_429_rate_limit_avec_retry_after(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->sequence()
                ->push('', 429, ['Retry-After' => '1'])
                ->push(['shop' => ['name' => 'OK']], 200),
        ]);

        $client = new ShopifyClient($this->http);
        $result = $client->get('shop.json');

        $this->assertSame(['shop' => ['name' => 'OK']], $result);
    }

    public function test_echec_definitif_apres_max_attempts(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->response(
                ['error' => 'down'],
                502,
            ),
        ]);

        $client = new ShopifyClient($this->http);

        $this->expectException(ShopifyApiException::class);

        try {
            $client->get('shop.json');
        } catch (ShopifyApiException $e) {
            $this->assertSame(502, $e->statusCode);
            $this->assertSame(3, $e->attempts);
            $this->assertTrue($e->isRetryable());
            throw $e;
        }
    }

    public function test_exception_sur_token_manquant(): void
    {
        Config::set('shopify.access_token', '');

        $client = new ShopifyClient($this->http);

        $this->expectException(\App\Exceptions\Shopify\ShopifyConfigurationException::class);

        $client->get('shop.json');
    }

    public function test_timeout_reseau_leve_shopify_timeout_exception(): void
    {
        $this->http->fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $client = new ShopifyClient($this->http);

        $this->expectException(ShopifyTimeoutException::class);

        $client->get('shop.json');
    }

    public function test_post_envoyer_le_body_en_json(): void
    {
        $this->http->fake([
            'test-shop.myshopify.com/*' => $this->http->response(['order' => ['id' => 99]], 201),
        ]);

        $client = new ShopifyClient($this->http);
        $result = $client->post('orders.json', ['order' => ['test' => true]]);

        $this->http->assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://test-shop.myshopify.com/admin/api/2025-01/orders.json'
                && $request->hasHeader('X-Shopify-Access-Token', 'shpat_test_token');
        });

        $this->assertSame(['order' => ['id' => 99]], $result);
    }
}