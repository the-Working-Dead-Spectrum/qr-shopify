<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\ValidationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateQrCodeRequest;
use App\Http\Resources\PartnerResource;
use App\Http\Resources\ValidationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller du scan de QR Code par un partenaire (PWA mobile).
 *
 * Endpoints :
 *  - POST /api/validate      : scan d'un QR Code
 *  - GET  /api/validations/my : 50 derniers scans du partenaire connecté
 *
 * Middleware appliqué dans routes/api.php :
 *  - auth:sanctum
 *  - ensure.partner
 *  - throttle:api
 *
 * Le Controller est ultra-fin :
 *  1. Valide l'input (FormRequest)
 *  2. Récupère le Partner depuis $request->attributes (injecté par EnsurePartner)
 *  3. Délègue au ValidationService
 *  4. Retourne la réponse JSON avec le bon code HTTP
 */
final class ValidationController extends Controller
{
    public function __construct(
        private readonly ValidationServiceInterface $validationService,
    ) {}

    /**
     * Scan d'un QR Code.
     *
     * POST /api/validate
     * Body : { "uuid": "<hmac hex 64 chars>" }
     */
    public function validate(ValidateQrCodeRequest $request): JsonResponse
    {
        // EnsurePartner a injecté le Partner résolu dans les attributes.
        $partner = $request->attributes->get('partner');

        $result = $this->validationService->validate(
            uuid: $request->uuid(),
            partner: $partner,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json(
            $result->toArray(),
            $result->httpCode(),
        );
    }

    /**
     * Historique des 50 derniers scans du partenaire connecté.
     *
     * GET /api/validations/my
     */
    public function myValidations(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validations = $partner->validations()
            ->with(['qrCode.order'])
            ->latest('scanned_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => ValidationResource::collection($validations),
            'count' => $validations->count(),
        ]);
    }

    /**
     * Profil du partenaire connecté.
     *
     * GET /api/partner/me
     */
    public function me(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $partner->load('user');

        return response()->json([
            'data' => new PartnerResource($partner),
        ]);
    }
}
