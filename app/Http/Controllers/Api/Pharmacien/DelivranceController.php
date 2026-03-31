<?php

namespace App\Http\Controllers\Api\Pharmacien;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Notification;
use Illuminate\Http\Request;

class DelivranceController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/pharmacien/delivrances",
     *     tags={"Pharmacien - Délivrances"},
     *     summary="Valider la délivrance d'une ordonnance",
     *     description="Marque une ordonnance comme délivrée et notifie le patient que ses médicaments sont prêts.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prescription_id"},
     *             @OA\Property(property="prescription_id", type="integer", example=1, description="ID de la prescription à délivrer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Délivrance validée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Délivrance validée, patient notifié"),
     *             @OA\Property(property="prescription", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Prescription non trouvée"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate(['prescription_id' => 'required|exists:prescriptions,id']);
        return $this->validerDelivrance($request->prescription_id);
    }

    /**
     * @OA\Get(
     *     path="/api/pharmacien/delivrances",
     *     tags={"Pharmacien - Délivrances"},
     *     summary="Lister les délivrances",
     *     description="Retourne la liste des ordonnances délivrées par la pharmacie.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des délivrances", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        $delivrances = Prescription::where('pharmacie_id', $pharmacien->pharmacie_id)
            ->where('statut', 'delivree')
            ->with(['medecin.user', 'medicaments', 'consultation.dossierMedical.patient.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($delivrances);
    }

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
