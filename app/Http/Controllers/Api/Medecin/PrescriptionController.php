<?php
namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/medecin/prescriptions",
     *     tags={"Médecin - Prescriptions"},
     *     summary="Lister les prescriptions du médecin",
     *     description="Retourne la liste paginée des prescriptions émises par le médecin connecté.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Liste des prescriptions paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;
        $prescriptions = Prescription::where('medecin_id', $medecin->id)
            ->with(['consultation.dossierMedical.patient.user', 'medicaments', 'pharmacie'])
            ->orderBy('date_emission', 'desc')
            ->paginate(20);

        return response()->json($prescriptions);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/prescriptions",
     *     tags={"Médecin - Prescriptions"},
     *     summary="Créer une prescription",
     *     description="Crée une ordonnance avec la liste des médicaments prescrits. Le numéro d'ordonnance est généré automatiquement.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"consultation_id","medicaments"},
     *             @OA\Property(property="consultation_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Prendre avec de la nourriture"),
     *             @OA\Property(property="pharmacie_id", type="integer", nullable=true),
     *             @OA\Property(
     *                 property="medicaments",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"medicament_id","posologie"},
     *                     @OA\Property(property="medicament_id", type="integer", example=1),
     *                     @OA\Property(property="posologie", type="string", example="2 comprimés matin et soir"),
     *                     @OA\Property(property="duree_traitement", type="integer", example=7, description="Durée en jours"),
     *                     @OA\Property(property="quantite", type="integer", example=14)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Prescription créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Prescription créée avec succès"),
     *             @OA\Property(property="prescription", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'notes' => 'nullable|string',
            'pharmacie_id' => 'nullable|exists:pharmacies,id',
            'medicaments' => 'required|array|min:1',
            'medicaments.*.medicament_id' => 'required|exists:medicaments,id',
            'medicaments.*.posologie' => 'required|string',
            'medicaments.*.duree_traitement' => 'nullable|integer',
            'medicaments.*.quantite' => 'nullable|integer',
        ]);

        $medecin = $request->user()->medecin;

        $prescription = Prescription::create([
            'consultation_id' => $request->consultation_id,
            'medecin_id' => $medecin->id,
            'numero' => 'RX-' . strtoupper(Str::random(8)),
            'date_emission' => now(),
            'date_expiration' => now()->addMonths(3),
            'statut' => 'active',
            'notes' => $request->notes,
            'pharmacie_id' => $request->pharmacie_id,
        ]);

        foreach ($request->medicaments as $med) {
            $prescription->medicaments()->attach($med['medicament_id'], [
                'posologie' => $med['posologie'],
                'duree_traitement' => $med['duree_traitement'] ?? null,
                'quantite' => $med['quantite'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Prescription créée avec succès',
            'prescription' => $prescription->load('medicaments')
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/prescriptions/{id}/envoyer-pharmacie",
     *     tags={"Médecin - Prescriptions"},
     *     summary="Envoyer une ordonnance à la pharmacie",
     *     description="Associe une pharmacie à l'ordonnance et change son statut en 'envoyée'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la prescription",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pharmacie_id"},
     *             @OA\Property(property="pharmacie_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Ordonnance envoyée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ordonnance envoyée à la pharmacie"),
     *             @OA\Property(property="prescription", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Prescription non trouvée"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function envoyerPharmacie(Request $request, string $id)
    {
        $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
        ]);

        $prescription = Prescription::findOrFail($id);
        $prescription->update([
            'pharmacie_id' => $request->pharmacie_id,
            'statut' => 'envoyee',
        ]);

        return response()->json([
            'message' => 'Ordonnance envoyée à la pharmacie',
            'prescription' => $prescription
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/prescriptions/{id}",
     *     tags={"Médecin - Prescriptions"},
     *     summary="Détails d'une prescription",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la prescription", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Prescription non trouvée")
     * )
     */
    public function show(string $id)
    {
        $prescription = Prescription::with(['consultation.dossierMedical.patient.user', 'medecin.user', 'medicaments', 'pharmacie'])->findOrFail($id);
        return response()->json($prescription);
    }

    /**
     * @OA\Delete(
     *     path="/api/medecin/prescriptions/{id}",
     *     tags={"Médecin - Prescriptions"},
     *     summary="Supprimer une prescription",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Prescription supprimée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Prescription supprimée"))
     *     ),
     *     @OA\Response(response=404, description="Prescription non trouvée")
     * )
     */
    public function destroy(string $id)
    {
        Prescription::findOrFail($id)->delete();
        return response()->json(['message' => 'Prescription supprimée']);
    }
}
