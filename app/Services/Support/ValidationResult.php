<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Enums\ValidationStatus;
use App\Models\QrCode;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Résultat immuable d'une tentative de validation de QR Code.
 *
 * Pourquoi un DTO plutôt qu'une string :
 *  - Le Controller a besoin du code HTTP (200/403/404/409/410)
 *  - Le Controller a besoin d'un message localisable
 *  - L'API Resource a besoin de données structurées (order_id, expires_at, used_at)
 *  - Le logging a besoin d'une raison métier uniforme
 *
 * Pattern : "Result Object" — évite les exceptions de contrôle de flux.
 */
final readonly class ValidationResult implements Arrayable
{
    /**
     * Mappe le statut métier vers le code HTTP selon SPECS §6.3.
     * Centralisé pour garantir la cohérence API.
     */
    public static function httpCodeFor(ValidationStatus $status): int
    {
        return match ($status) {
            ValidationStatus::Valid => 200,
            ValidationStatus::AlreadyUsed => 409,
            ValidationStatus::Expired => 410,
            ValidationStatus::Revoked => 403,
            ValidationStatus::Invalid => 404,
        };
    }

    // -------------------------------------------------------------------------
    // Factories — rendent l'intention explicite côté appelant
    // -------------------------------------------------------------------------

    public static function valid(QrCode $qr, int $partnerId): self
    {
        return new self(
            status: ValidationStatus::Valid,
            httpCode: 200,
            message: 'QR Code validé avec succès.',
            orderId: $qr->order_id,
            expiresAt: $qr->expires_at,
            usedAt: $qr->used_at,
            validatedByPartnerId: $partnerId,
        );
    }

    public static function alreadyUsed(QrCode $qr): self
    {
        return new self(
            status: ValidationStatus::AlreadyUsed,
            httpCode: 409,
            message: 'Ce QR Code a déjà été utilisé.',
            orderId: $qr->order_id,
            usedAt: $qr->used_at,
        );
    }

    public static function expired(QrCode $qr): self
    {
        return new self(
            status: ValidationStatus::Expired,
            httpCode: 410,
            message: 'Ce QR Code a expiré.',
            orderId: $qr->order_id,
            expiresAt: $qr->expires_at,
        );
    }

    public static function revoked(QrCode $qr): self
    {
        return new self(
            status: ValidationStatus::Revoked,
            httpCode: 403,
            message: 'Ce QR Code a été révoqué.',
            orderId: $qr->order_id,
        );
    }

    public static function invalid(): self
    {
        return new self(
            status: ValidationStatus::Invalid,
            httpCode: 404,
            message: 'QR Code introuvable.',
        );
    }

    public function __construct(
        public ValidationStatus $status,
        public int $httpCode,
        public string $message,
        public ?int $orderId = null,
        public ?DateTimeInterface $expiresAt = null,
        public ?DateTimeInterface $usedAt = null,
        public ?int $validatedByPartnerId = null,
    ) {}

    public function isValid(): bool
    {
        return $this->status === ValidationStatus::Valid;
    }

    public function httpCode(): int
    {
        return $this->httpCode;
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status->value,
            'message' => $this->message,
            'order_id' => $this->orderId,
            'expires_at' => $this->expiresAt?->format(DateTimeInterface::ATOM),
            'used_at' => $this->usedAt?->format(DateTimeInterface::ATOM),
            'partner_id' => $this->validatedByPartnerId,
        ], static fn ($v): bool => $v !== null);
    }
}
