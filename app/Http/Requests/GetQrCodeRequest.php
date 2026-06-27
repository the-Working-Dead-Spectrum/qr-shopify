<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation d'un UUID de QR Code en paramètre de route.
 *
 * Endpoint : GET /api/qr/{uuid}
 *
 * Le {uuid} est passé via l'URL, donc on valide via le route param.
 * Le format est identique à ValidateQrCodeRequest (64 hex).
 */
final class GetQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
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
                'size:64',
                'regex:/^[a-f0-9]+$/i',
            ],
        ];
    }

    public function uuid(): string
    {
        return strtolower((string) $this->route('uuid'));
    }
}
