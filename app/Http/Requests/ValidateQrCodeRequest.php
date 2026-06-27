<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du scan de QR Code par un partenaire.
 *
 * Endpoint : POST /api/validate
 * Body : { "uuid": "<hash HMAC>" }
 *
 * Règles :
 *  - uuid obligatoire
 *  - format hexadécimal strict (64 caractères exactement → HMAC-SHA256)
 *  - pas de sanitisation excessive : on stocke tel quel, la DB rejette les doublons
 *
 * Note : on n'utilise pas 'string' Laravel basique car on veut le format hex exact.
 */
final class ValidateQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'authentification est gérée par le middleware auth:sanctum.
        // authorize() doit toujours retourner true ici, sinon le middleware
        // n'aurait pas atteint ce point.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'uuid' => [
                'required',
                'string',
                'size:64',          // HMAC-SHA256 hex = exactement 64 caractères
                'regex:/^[a-f0-9]+$/i',  // hexadécimal uniquement
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uuid.required' => 'Le QR Code est obligatoire.',
            'uuid.string' => 'Le QR Code doit être une chaîne de caractères.',
            'uuid.size' => 'Le QR Code est invalide (format attendu : 64 caractères hexadécimaux).',
            'uuid.regex' => 'Le QR Code contient des caractères non autorisés.',
        ];
    }

    /**
     * UUID nettoyé et normalisé (lowercase) pour lookup.
     */
    public function uuid(): string
    {
        return strtolower((string) $this->input('uuid'));
    }
}
