<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QRCodeService;

class QRCodeController extends Controller
{
    public function __construct(private QRCodeService $qrCodeService) {}

    /**
     * @OA\Get(
     *     path="/api/patient/qrcode",
     *     tags={"Patient - QR Code"},
     *     summary="Générer le QR Code du patient connecté",
     *     description="Génère un QR Code contenant les informations d'identification du patient (ID, numéro de dossier, nom, prénom). Retourné en base64.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="QR Code généré",
     *         @OA\JsonContent(
     *             @OA\Property(property="qr_code", type="string", description="Image SVG encodée en base64"),
     *             @OA\Property(property="num_dossier", type="string", example="DS-000001")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function monQRCode(Request $request)
    {
        $patient = $request->user()->patient;
        $qrCode = $this->qrCodeService->genererQRCode($patient);

        return response()->json([
            'qr_code' => base64_encode($qrCode['svg']),
            'num_dossier' => $patient->num_dossier,
            'payload' => $qrCode['payload'],
            'expires_at' => $qrCode['expires'],
        ]);
    }

    public function scan(string $code)
    {
        $patient = $this->resolvePatientFromQrInput($code);

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé'], 404);
        }

        return response()->json($patient);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/qrcode/scanner",
     *     tags={"Patient - QR Code"},
     *     summary="Scanner le QR Code d'un patient (médecin)",
     *     description="Permet au médecin de scanner le QR Code d'un patient pour accéder à son dossier médical complet.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="DS-000001", description="Numéro de dossier ou contenu du QR Code")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Données du patient",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Patient non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function scanner(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        return $this->scan($request->code);
    }

    public function generer(Request $request)
    {
        return $this->monQRCode($request);
    }

    private function resolvePatientFromQrInput(string $code)
    {
        $raw = trim($code);
        $payload = $this->extractPayload($raw);

        if (preg_match('#/urgence/patient/([a-f0-9]{64})$#i', $payload, $matches)) {
            $patient = $this->qrCodeService->validerToken($matches[1]);
            if ($patient) {
                return $patient->load([
                    'user',
                    'dossierMedical.consultations.medecin.user',
                    'dossierMedical.resultatsAnalyses',
                    'carnetVaccination.vaccins',
                ]);
            }
        }

        return \App\Models\Patient::where('num_dossier', $payload)
            ->with([
                'user',
                'dossierMedical.consultations.medecin.user',
                'dossierMedical.resultatsAnalyses',
                'carnetVaccination.vaccins',
            ])->first();
    }

    private function extractPayload(string $code): string
    {
        $json = json_decode($code, true);

        if (is_array($json)) {
            if (!empty($json['payload'])) {
                return trim((string) $json['payload']);
            }

            if (!empty($json['num_dossier'])) {
                return trim((string) $json['num_dossier']);
            }
        }

        return $code;
    }
}
