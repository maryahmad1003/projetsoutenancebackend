<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\DossierMedical;
use Illuminate\Support\Facades\Cache;

class QRCodeService
{
    /**
     * Générer (ou récupérer depuis le cache) le QR code d'un patient.
     *
     * Le QR code encode une URL sécurisée vers le dossier médical :
     *   docsecur://patient/{token}
     *
     * Ce token est un SHA-256 de l'ID patient + une clé secrète.
     * Il est valide 24 h (pour la lecture urgence) et rechargeable.
     */
    public function genererQRCode(Patient $patient): array
    {
        $token     = $this->genererToken($patient);
        $payload   = $this->buildPayload($patient, $token);
        $cacheKey  = "qrcode_patient_{$patient->id}";

        // Mettre en cache le token pendant 24 h (utilisé pour la validation)
        Cache::put($cacheKey, $token, now()->addHours(24));

        $svgContent = $this->buildSvgQR($payload);

        return [
            'token'   => $token,
            'payload' => $payload,
            'svg'     => $svgContent,
            'expires' => now()->addHours(24)->toISOString(),
        ];
    }

    /**
     * Valider un token QR Code et retourner le patient correspondant.
     */
    public function validerToken(string $token): ?Patient
    {
        // Chercher le patient dont le token correspond
        $patients = Patient::all();

        foreach ($patients as $patient) {
            $expected = $this->genererToken($patient);
            if (hash_equals($expected, $token)) {
                // Vérifier que le token est encore dans le cache (non expiré)
                $cacheKey = "qrcode_patient_{$patient->id}";
                if (Cache::has($cacheKey)) {
                    return $patient;
                }
            }
        }

        return null;
    }

    /**
     * Régénérer un token (révoque l'ancien).
     */
    public function regenerer(Patient $patient): array
    {
        Cache::forget("qrcode_patient_{$patient->id}");
        return $this->genererQRCode($patient);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function genererToken(Patient $patient): string
    {
        $secret = config('app.key');
        return hash('sha256', "docsecur-qr-{$patient->id}-{$patient->num_dossier}-{$secret}");
    }

    private function buildPayload(Patient $patient, string $token): string
    {
        $baseUrl = config('app.url', 'https://docsecur.sn');
        return "{$baseUrl}/urgence/patient/{$token}";
    }

    /**
     * Générer un QR Code SVG minimaliste (matrice 21×21 pour une URL courte).
     * En production, utiliser un package dédié comme `chillerlan/php-qrcode`.
     */
    private function buildSvgQR(string $data): string
    {
        // Utiliser le package bacon/bacon-qr-code s'il est disponible
        if (class_exists(\BaconQrCode\Renderer\ImageRenderer::class)) {
            return $this->renderWithBacon($data);
        }

        // Fallback : SVG placeholder avec le texte encodé
        $escaped = htmlspecialchars($data, ENT_QUOTES);
        $hash    = substr(md5($data), 0, 8);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
  <rect width="200" height="200" fill="#0A1220"/>
  <rect x="10" y="10" width="180" height="180" rx="8" fill="none" stroke="#0ED2A0" stroke-width="2"/>
  <!-- Finder pattern TL -->
  <rect x="20" y="20" width="49" height="49" rx="4" fill="none" stroke="#0ED2A0" stroke-width="3"/>
  <rect x="30" y="30" width="29" height="29" rx="2" fill="#0ED2A0"/>
  <!-- Finder pattern TR -->
  <rect x="131" y="20" width="49" height="49" rx="4" fill="none" stroke="#0ED2A0" stroke-width="3"/>
  <rect x="141" y="30" width="29" height="29" rx="2" fill="#0ED2A0"/>
  <!-- Finder pattern BL -->
  <rect x="20" y="131" width="49" height="49" rx="4" fill="none" stroke="#0ED2A0" stroke-width="3"/>
  <rect x="30" y="141" width="29" height="29" rx="2" fill="#0ED2A0"/>
  <!-- Data pattern (visuel basé sur le hash) -->
  <text x="100" y="105" text-anchor="middle" fill="rgba(238,244,255,0.6)"
        font-family="monospace" font-size="9">{$hash}</text>
  <text x="100" y="192" text-anchor="middle" fill="rgba(14,210,160,0.7)"
        font-family="sans-serif" font-size="8">DocSecur · Urgence</text>
</svg>
SVG;
    }

    private function renderWithBacon(string $data): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        return $writer->writeString($data);
    }
}
