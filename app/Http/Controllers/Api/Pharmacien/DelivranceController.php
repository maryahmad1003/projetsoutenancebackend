<?php

namespace App\Http\Controllers\Api\Pharmacien;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Notification;
use Illuminate\Http\Request;

class DelivranceController extends Controller
{
    public function validerDelivrance(string $id)
    {
        $prescription = Prescription::with('consultation.dossierMedical.patient.user')->findOrFail($id);
        $prescription->update(['statut' => 'delivree']);

        $patientUser = $prescription->consultation->dossierMedical->patient->user;

        Notification::create([
            'user_id' => $patientUser->id,
            'type' => 'medicament_pret',
            'message' => 'Vos médicaments sont prêts à être retirés à la pharmacie.',
            'canal' => 'sms',
            'date_envoi' => now(),
        ]);

        return response()->json(['message' => 'Délivrance validée, patient notifié', 'prescription' => $prescription]);
    }
}