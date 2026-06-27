<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PartnerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création / mise à jour d'un partenaire.
 *
 * Endpoint création : POST /admin/partners
 * Endpoint mise à jour : PATCH /admin/partners/{id}
 *
 * Règles :
 *  - email unique (sauf pour l'utilisateur courant en update)
 *  - nom obligatoire, longueur raisonnable
 *  - status énuméré (validation native via enum)
 */
final class PartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'accès est filtré par EnsureAdmin.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $partnerId = $this->route('partner')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                // Unique sur users.email : on vérifie l'unicité de l'email utilisateur.
                // Le slug du Partner sera dérivé du nom.
                $this->isMethod('POST')
                    ? 'unique:users,email'
                    : "unique:users,email,{$this->user()?->id}",
            ],
            'status' => ['required', Rule::enum(PartnerStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du partenaire est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email n\'est pas valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'status.required' => 'Le statut est obligatoire.',
            'status.enum' => 'Le statut doit être active, inactive ou suspended.',
        ];
    }
}
