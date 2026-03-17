<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeController extends Controller
{
    public function monQRCode(Request $request)
    {
        $patient = $request->user()->patient;
        $qrCode = QrCode::size(300)->generate(json_encode([
            'patient_id' => $patient->id,
            'num_dossier' => $patient->num_dossier,
            'nom' => $request->user()->nom,
            'prenom' => $request->user()->prenom,
        ]));

        return response()->json([
            'qr_code' => base64_encode($qrCode),
            'num_dossier' => $patient->num_dossier,
        ]);
    }

    public function scan(string $code)
    {
        $patient = Patient::where('num_dossier', $code)
            ->with([
                'user',
                'dossierMedical.consultations.medecin.user',
                'dossierMedical.resultatsAnalyses',
                'carnetVaccination.vaccins',
            ])->first();

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé'], 404);
        }

        return response()->json($patient);
    }
}