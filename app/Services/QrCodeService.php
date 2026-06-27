<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\QrCodeGeneratorInterface;
use App\Enums\QrStatus;
use App\Exceptions\Service\QrSecretNotConfiguredException;
use App\Models\Order;
use App\Models\QrCode;
use App\Services\Concerns\LogsServiceActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeFacade;
use Throwable;

/**
 * Service de génération et régénération des QR Codes.
 *
 * Responsabilités :
 *  1. Générer un UUID v4 (122 bits d'entropie) — jamais prévisible
 *  2. Le signer avec HMAC-SHA256 via APP_QR_SECRET — non réversible
 *  3. Stocker le HMAC en base (jamais l'UUID brut)
 *  4. Rendre une image PNG du QR encodant l'URL publique
 *  5. Supporter la régénération (invalidation de l'ancien)
 *
 * Invariants de sécurité :
 *  - Le secret HMAC doit être configuré (sinon échec dur)
 *  - Le HMAC a toujours 64 caractères hexadécimaux
 *  - L'UUID brut n'est jamais persisté, jamais loggué
 */
final class QrCodeService implements QrCodeGeneratorInterface
{
    use LogsServiceActivity;

    /**
     * Secret HMAC mis en cache par instance pour éviter config() répétés.
     */
    private ?string $cachedSecret = null;

    public function generate(Order $order, ?QrCode $regenerateFrom = null): QrCode
    {
        return DB::transaction(function () use ($order, $regenerateFrom): QrCode {
            // Cas régénération : on révoque l'ancien QR avant d'en créer un nouveau.
            // Volontairement séquentiel : si l'ancien n'est pas révoqué,
            // deux QR pourraient être valides pour la même commande.
            if ($regenerateFrom !== null) {
                $this->revokePrevious($regenerateFrom);
            }

            $uuid = $this->newUuid();
            $signedUuid = $this->signUuid($uuid);
            $ttlDays = (int) config('qr.ttl_days', 7);

            $qrCode = QrCode::create([
                'uuid' => $signedUuid,
                'order_id' => $order->id,
                'status' => QrStatus::Active,
                'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
                'partner_id' => null,
                'regenerated_from' => $regenerateFrom?->id,
            ]);

            $this->info('qr_code.generated', [
                'qr_id' => $qrCode->id,
                'order_id' => $order->id,
                'ttl_days' => $ttlDays,
                'is_regen' => $regenerateFrom !== null,
                'old_qr_id' => $regenerateFrom?->id,
            ]);

            return $qrCode->refresh();
        });
    }

    public function generateImage(string $uuid): string
    {
        $url = $this->buildPublicUrl($uuid);
        $size = (int) config('qr.size_px', 400);

        try {
            // Format PNG, error correction 'H' (haute — 30%) pour résister
            // aux scans partiels, à la déformation, et au logotage partiel.
            $png = QrCodeFacade::format('png')
                ->size($size)
                ->errorCorrection('H')
                ->margin(1)
                ->generate($url);

            return base64_encode((string) $png);
        } catch (Throwable $e) {
            $this->error('qr_code.image_generation_failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function signUuid(string $uuid): string
    {
        $secret = $this->secret();

        // hash_hmac retourne du binaire. On encode en hex pour avoir
        // 64 chars stables, lisibles en DB, et compatibles avec VARCHAR(256).
        return hash_hmac('sha256', $uuid, $secret);
    }

    // -------------------------------------------------------------------------
    // API interne
    // -------------------------------------------------------------------------

    /**
     * Régénère un QR pour une commande : annule l'ancien + crée le nouveau.
     * Centralisé ici pour que Controllers / Jobs / Commands l'utilisent uniformément.
     *
     * @throws ModelNotFoundException Si l'Order n'existe pas.
     */
    public function regenerate(Order $order): QrCode
    {
        $current = $order->qrCode; // utilise la relation hasOne latest

        if ($current === null) {
            // Pas de QR actif — on en crée un neuf, ce n'est pas une régénération.
            return $this->generate($order);
        }

        return $this->generate($order, $current);
    }

    /**
     * Construit l'URL publique encodée dans le QR.
     * Délégué pour permettre une substitution facile (ex: sous-domaine dédié).
     */
    protected function buildPublicUrl(string $uuid): string
    {
        return route('qr.show', ['uuid' => $uuid]);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Génère un UUID v4 (122 bits d'entropie).
     * Str::uuid() délègue à Ramsey\Uuid côté Laravel.
     */
    private function newUuid(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Récupère et valide la présence du secret HMAC.
     *
     * @throws QrSecretNotConfiguredException Si APP_QR_SECRET n'est pas défini.
     */
    private function secret(): string
    {
        if ($this->cachedSecret !== null) {
            return $this->cachedSecret;
        }

        $secret = (string) config('qr.secret', '');

        if ($secret === '') {
            throw new QrSecretNotConfiguredException(
                'APP_QR_SECRET n\'est pas configuré. Générer avec : openssl rand -base64 32',
            );
        }

        // SPECS §6.6 : ≥ 256 bits. base64(32) = 256 bits.
        if (strlen($secret) < 32) {
            throw new QrSecretNotConfiguredException(
                'APP_QR_SECRET trop court (minimum 32 caractères). Régénérer avec : openssl rand -base64 32',
            );
        }

        return $this->cachedSecret = $secret;
    }

    /**
     * Révoque l'ancien QR lors d'une régénération.
     * Doit être appelé dans la même transaction que la création du nouveau.
     */
    private function revokePrevious(QrCode $previous): void
    {
        if ($previous->status === QrStatus::Revoked) {
            return; // déjà révoqué, rien à faire
        }

        $previous->update([
            'status' => QrStatus::Revoked,
            'used_at' => $previous->used_at, // inchangé — on garde la traçabilité
        ]);

        $this->info('qr_code.previous_revoked', [
            'old_qr_id' => $previous->id,
            'order_id' => $previous->order_id,
        ]);
    }
}
