<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Support;

use App\Exceptions\Service\InvalidPayloadException;
use App\Services\Support\OrderPayload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du DTO OrderPayload.
 *
 * Le DTO est la frontière entre le payload Shopify brut (non-typé) et
 * notre domaine. Une erreur ici se propage silencieusement partout.
 * On teste donc TOUS les chemins d'extraction.
 */
final class OrderPayloadTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction depuis un payload Shopify "orders/paid"
    // -------------------------------------------------------------------------

    #[Test]
    public function it_extracts_a_complete_payload(): void
    {
        $payload = [
            'id'                  => '1234567890123',
            'email'               => 'client@example.com',
            'total_price'         => '49.99',
            'currency'            => 'EUR',
            'customer' => [
                'first_name' => 'Marie',
                'last_name'  => 'Dupont',
            ],
        ];

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('1234567890123', $dto->shopifyOrderId);
        $this->assertSame('client@example.com', $dto->customerEmail);
        $this->assertSame('Marie Dupont', $dto->customerName);
        $this->assertSame(4999, $dto->amountCents);
        $this->assertSame('EUR', $dto->currency);
        $this->assertNull($dto->cancelledAt);
        $this->assertFalse($dto->isCancelled());
    }

    #[Test]
    public function it_accepts_integer_id(): void
    {
        $payload = $this->validPayload(['id' => 12345]);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('12345', $dto->shopifyOrderId);
    }

    #[Test]
    public function it_falls_back_to_admin_graphql_id_if_id_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['id']);
        $payload['admin_graphql_api_id'] = 'gid://shopify/Order/999';

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('gid://shopify/Order/999', $dto->shopifyOrderId);
    }

    #[Test]
    public function it_extracts_email_from_customer_when_root_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);
        $payload['customer'] = [
            'first_name' => 'Paul',
            'last_name'  => 'Martin',
            'email'      => 'paul.martin@example.com',
        ];

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('paul.martin@example.com', $dto->customerEmail);
    }

    #[Test]
    public function it_extracts_email_from_contact_email_as_last_resort(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);
        $payload['contact_email'] = 'contact@example.com';

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('contact@example.com', $dto->customerEmail);
    }

    #[Test]
    public function it_returns_null_customer_name_when_only_whitespace(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'first_name' => '   ',
                'last_name'  => '',
            ],
        ]);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertNull($dto->customerName);
    }

    #[Test]
    public function it_returns_null_customer_name_when_customer_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['customer']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertNull($dto->customerName);
    }

    #[Test]
    public function it_converts_decimal_price_to_cents_correctly(): void
    {
        $payload = $this->validPayload(['total_price' => '123.45']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame(12345, $dto->amountCents);
    }

    #[Test]
    public function it_rounds_prices_with_more_than_two_decimals(): void
    {
        // 49.999 € → 4999.9 → round() → 5000 cents
        $payload = $this->validPayload(['total_price' => '49.999']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame(5000, $dto->amountCents);
    }

    #[Test]
    public function it_defaults_amount_to_zero_when_price_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['total_price']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame(0, $dto->amountCents);
    }

    #[Test]
    public function it_defaults_currency_to_eur(): void
    {
        $payload = $this->validPayload();
        unset($payload['currency']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertSame('EUR', $dto->currency);
    }

    // -------------------------------------------------------------------------
    // Cancellation
    // -------------------------------------------------------------------------

    #[Test]
    public function it_marks_cancelled_payload_correctly(): void
    {
        $payload = $this->validPayload(['cancelled_at' => '2026-01-15T10:00:00Z']);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertTrue($dto->isCancelled());
        $this->assertSame('2026-01-15T10:00:00Z', $dto->cancelledAt);
    }

    #[Test]
    public function it_ignores_non_string_cancelled_at(): void
    {
        $payload = $this->validPayload(['cancelled_at' => ['unexpected']]);

        $dto = OrderPayload::fromShopify($payload);

        $this->assertNull($dto->cancelledAt);
        $this->assertFalse($dto->isCancelled());
    }

    // -------------------------------------------------------------------------
    // Cas d'erreur
    // -------------------------------------------------------------------------

    #[Test]
    public function it_throws_when_id_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['id']);

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Champ "id" manquant');

        OrderPayload::fromShopify($payload);
    }

    #[Test]
    public function it_throws_when_id_is_not_scalar(): void
    {
        $payload = $this->validPayload(['id' => ['array']]);

        $this->expectException(InvalidPayloadException::class);

        OrderPayload::fromShopify($payload);
    }

    #[Test]
    public function it_throws_when_email_is_missing_everywhere(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Email client manquant');

        OrderPayload::fromShopify($payload);
    }

    #[Test]
    public function it_throws_when_email_is_empty_string(): void
    {
        $payload = $this->validPayload(['email' => '']);

        $this->expectException(InvalidPayloadException::class);

        OrderPayload::fromShopify($payload);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'id'           => '1234567890123',
            'email'        => 'client@example.com',
            'total_price'  => '49.99',
            'currency'     => 'EUR',
            'customer'     => [
                'first_name' => 'Marie',
                'last_name'  => 'Dupont',
            ],
        ], $overrides);
    }
}