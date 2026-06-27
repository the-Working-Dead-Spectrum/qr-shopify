<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\QrCodeGeneratorInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetQrCodeRequest;
use App\Http\Resources\QrCodeResource;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

/**
 * Controller des endpoints QR Code.
 *
 * Endpoints (cf. SPECS §7.2) :
 *  - GET    /api/qr/{uuid}             : informations d'un QR Code
 *  - POST   /api/orders/{id}/qr/regenerate : régénération (admin uniquement)
 *  - GET    /api/qr/{uuid}/download    : téléchargement du PNG
 *  - GET    /qr/{uuid}                 : page publique (rendue Blade)
 *
 * Pourquoi GET /api/qr/{uuid} :
 *  - Utile pour debug et pour l'admin qui veut voir les détails d'un QR
 *  - Pas d'info sensible (pas d'email client en clair)
 *
 * Pourquoi pas POST /api/validate ici :
 *  - La validation est dans ValidationController (séparation des responsabilités)
 *  - QrCodeController = CRUD QR, ValidationController = scan
 */
final class QrCodeController extends Controller
{
    public function __construct(
        private readonly QrCodeGeneratorInterface $qrCodeService,
    ) {}

    /**
     * Détails d'un QR Code.
     *
     * GET /api/qr/{uuid}
     */
    public function show(GetQrCodeRequest $request): JsonResponse
    {
        $qrCode = QrCode::with(['order', 'partner'])
            ->where('uuid', $request->uuid())
            ->first();

        if ($qrCode === null) {
            return response()->json(['error' => 'QR Code introuvable.'], 404);
        }

        return response()->json([
            'data' => new QrCodeResource($qrCode),
        ]);
    }

    /**
     * Régénération d'un QR Code (action admin).
     *
     * POST /api/orders/{order}/qr/regenerate
     *
     * Séquence :
     *  1. Révoque l'ancien QR (déjà géré par QrCodeService::regenerate)
     *  2. Crée un nouveau QR
     *  3. Dispatch le job de ré-envoi email
     *
     * ⚠️ Action sensible : loggée dans activity_log (via le Model morphTo).
     */
    public function regenerate(Order $order): JsonResponse
    {
        $previousQr = $order->qrCode;
        $newQr = $this->qrCodeService->regenerate($order);

        // Log activité admin (sera complété en Phase 6 avec l'AuthController
        // pour avoir l'utilisateur courant ; ici on log sans user si absent)
        try {
            ActivityLog::record(
                user: Auth::user() ?? new User,
                action: 'qr.regenerated',
                subject: $newQr,
                properties: [
                    'order_id' => $order->id,
                    'previous_qr_id' => $previousQr?->id,
                    'new_qr_id' => $newQr->id,
                ],
            );
        } catch (Throwable) {
            // Log non-bloquant — l'activité admin ne doit pas faire échouer la régénération.
        }

        return response()->json([
            'message' => 'QR Code régénéré. Un email sera envoyé au client.',
            'data' => new QrCodeResource($newQr->load('order')),
        ], 201);
    }

    /**
     * Téléchargement direct du PNG.
     *
     * GET /api/qr/{uuid}/download
     */
    public function download(GetQrCodeRequest $request): Response
    {
        $qrCode = QrCode::where('uuid', $request->uuid())->first();

        if ($qrCode === null) {
            return response()->json(['error' => 'QR Code introuvable.'], 404);
        }

        $pngBase64 = $this->qrCodeService->generateImage($qrCode->uuid);
        $pngBinary = base64_decode($pngBase64, strict: true);

        return response($pngBinary, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"qr-{$qrCode->id}.png\"",
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Page publique de validation (rendue HTML).
     *
     * GET /qr/{uuid}
     *
     * Sert de fallback si le client clique sur le lien de l'email
     * plutôt que de scanner l'image inline.
     */
    public function publicPage(GetQrCodeRequest $request): View|JsonResponse
    {
        $qrCode = QrCode::with('order')->where('uuid', $request->uuid())->first();

        if ($qrCode === null) {
            return response()->json(['error' => 'QR Code introuvable.'], 404);
        }

        return view('qr.show', [
            'qrCode' => $qrCode,
        ]);
    }
}
