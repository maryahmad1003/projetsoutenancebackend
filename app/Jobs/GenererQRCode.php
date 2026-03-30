<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Services\QRCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job : générer (ou régénérer) le QR code d'identification urgence d'un patient.
 * Dispatch :
 *   GenererQRCode::dispatch($patient);
 */
class GenererQRCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(private Patient $patient) {}

    public function handle(QRCodeService $qrCodeService): void
    {
        try {
            $result = $qrCodeService->genererQRCode($this->patient);

            // Sauvegarder le SVG dans le stockage (accessible via /storage/qrcodes/)
            $path = "qrcodes/patient_{$this->patient->id}.svg";
            Storage::disk('public')->put($path, $result['svg']);

            Log::info("GenererQRCode: QR code généré pour patient #{$this->patient->id} → storage/{$path}");
        } catch (\Exception $e) {
            Log::error("GenererQRCode: échec pour patient #{$this->patient->id} — " . $e->getMessage());
            throw $e;
        }
    }
}
