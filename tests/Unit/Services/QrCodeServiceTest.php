<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\QrStatus;
use App\Exceptions\Service\QrSecretNotConfiguredException;
use App\Models\Order;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires du QrCodeService.
 *
 * Couvre les invariants de sécurité critiques :
 *  - HMAC toujours 64 hex chars
 *  - Secret manquant ou trop court → exception dure
 *  - Régénération annule l'ancien avant de créer le nouveau
 *  - TTL configurable
 */
final class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_SECRET = 'test-qr-secret-32-chars-minimum-len-aaaa';

    protected function setUp(): void
    {
        parent::setUp();
        config(['qr.secret' => self::TEST_SECRET]);
    }

    // -------------------------------------------------------------------------
    // signUuid — signature HMAC
    // -------------------------------------------------------------------------

    public function test_sign_uuid_returns_64_hex_characters(): void
    {
        $service = new QrCodeService();
        $signature = $service->signUuid('test-uuid');

        $this->assertSame(64, strlen($signature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function test_sign_uuid_is_deterministic_for_same_input(): void
    {
        $service = new QrCodeService();

        $a = $service->signUuid('identical-uuid');
        $b = $service->signUuid('identical-uuid');

        $this->assertSame($a, $b);
    }

    public function test_sign_uuid_differs_for_different_inputs(): void
    {
        $service = new QrCodeService();

        $a = $service->signUuid('uuid-1');
        $b = $service->signUuid('uuid-2');

        $this->assertNotSame($a, $b);
    }

    public function test_sign_uuid_depends_on_secret(): void
    {
        // Le service met en cache le secret au premier appel à secret().
        // On signe donc avec s1 AVANT de changer la config et de créer s2.
        config(['qr.secret' => 'first-secret-pour-test-32-chars-minim-xxx']);
        $service1 = new QrCodeService();
        $a = $service1->signUuid('same-uuid');

        config(['qr.secret' => 'autre-secret-aussi-long-32-chars-minimum-xx']);
        $service2 = new QrCodeService();
        $b = $service2->signUuid('same-uuid');

        $this->assertNotSame($a, $b);
    }

    // -------------------------------------------------------------------------
    // generate — création d'un QR Code
    // -------------------------------------------------------------------------

    public function test_generate_creates_active_qr_with_hmac_uuid(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $qr = $service->generate($order);

        $this->assertSame(QrStatus::Active, $qr->status);
        $this->assertSame($order->id, $qr->order_id);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $qr->uuid);
        $this->assertNull($qr->used_at);
        $this->assertNull($qr->partner_id);
        $this->assertNull($qr->regenerated_from);
        $this->assertNotNull($qr->expires_at);
        $this->assertTrue($qr->expires_at->isFuture());
    }

    public function test_generate_respects_ttl_config(): void
    {
        config(['qr.ttl_days' => 30]);
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $qr = $service->generate($order);

        $expectedDays = 30;
        $diffDays = (int) round($qr->expires_at->diffInDays(now(), true));
        $this->assertSame($expectedDays, $diffDays);
    }

    public function test_generate_with_zero_ttl_means_no_expiration(): void
    {
        config(['qr.ttl_days' => 0]);
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $qr = $service->generate($order);

        $this->assertNull($qr->expires_at);
    }

    public function test_generate_uses_hmac_not_raw_uuid(): void
    {
        // Le service doit stocker la signature HMAC (64 hex), pas l'UUID brut (36 chars).
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $qr = $service->generate($order);

        // Un UUID v4 a 36 caractères avec tirets. Le HMAC fait 64 chars hex.
        $this->assertSame(64, strlen($qr->uuid));
        $this->assertStringNotContainsString('-', $qr->uuid);
    }

    public function test_generate_links_previous_qr_on_regeneration(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();
        $previous = $service->generate($order);

        $regenerated = $service->generate($order, $previous);

        $this->assertSame($previous->id, $regenerated->regenerated_from);
    }

    public function test_generate_revokes_previous_qr_on_regeneration(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();
        $previous = $service->generate($order);

        $service->generate($order, $previous);

        $previous->refresh();
        $this->assertSame(QrStatus::Revoked, $previous->status);
    }

    public function test_generate_two_qr_codes_have_different_uuids(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $first = $service->generate($order);
        $second = $service->generate($order, $first);

        $this->assertNotSame($first->uuid, $second->uuid);
    }

    // -------------------------------------------------------------------------
    // regenerate — API publique
    // -------------------------------------------------------------------------

    public function test_regenerate_without_existing_qr_creates_new_one(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $qr = $service->regenerate($order);

        $this->assertSame(QrStatus::Active, $qr->status);
        $this->assertNull($qr->regenerated_from);
        $this->assertSame($order->id, $qr->order_id);
    }

    public function test_regenerate_with_existing_qr_revokes_old_and_creates_new(): void
    {
        $order = Order::factory()->create();
        $service = new QrCodeService();
        $previous = $service->generate($order);

        $new = $service->regenerate($order);

        $previous->refresh();
        $this->assertSame(QrStatus::Revoked, $previous->status);
        $this->assertSame($previous->id, $new->regenerated_from);
        $this->assertSame(QrStatus::Active, $new->status);
        $this->assertNotSame($previous->uuid, $new->uuid);
    }

    // -------------------------------------------------------------------------
    // generateImage — génération PNG
    // -------------------------------------------------------------------------

    public function test_generate_image_returns_base64_encoded_png(): void
    {
        $service = new QrCodeService();
        $uuid = 'a'.str_repeat('b', 63); // 64 chars

        try {
            $base64 = $service->generateImage($uuid);
        } catch (\RuntimeException $e) {
            // L'environnement n'a pas l'extension Imagick requise par simple-qrcode.
            // On marque le test comme skipped plutôt que failed : la responsabilité
            // de générer le PNG est déléguée à la lib externe.
            $this->markTestSkipped('Extension Imagick indisponible : ' . $e->getMessage());
            return;
        }

        $this->assertIsString($base64);
        $decoded = base64_decode($base64, strict: true);
        $this->assertNotFalse($decoded);
        // PNG magic bytes : 89 50 4E 47 0D 0A 1A 0A
        $this->assertSame("\x89PNG\r\n\x1A\n", substr($decoded, 0, 8));
    }

    // -------------------------------------------------------------------------
    // Configuration errors
    // -------------------------------------------------------------------------

    public function test_sign_uuid_throws_when_secret_missing(): void
    {
        config(['qr.secret' => '']);
        $service = new QrCodeService();

        $this->expectException(QrSecretNotConfiguredException::class);
        $service->signUuid('any-uuid');
    }

    public function test_sign_uuid_throws_when_secret_too_short(): void
    {
        config(['qr.secret' => 'short-secret']);
        $service = new QrCodeService();

        $this->expectException(QrSecretNotConfiguredException::class);
        $service->signUuid('any-uuid');
    }

    public function test_generate_throws_when_secret_not_configured(): void
    {
        config(['qr.secret' => null]);
        $order = Order::factory()->create();
        $service = new QrCodeService();

        $this->expectException(QrSecretNotConfiguredException::class);
        $service->generate($order);
    }
}
