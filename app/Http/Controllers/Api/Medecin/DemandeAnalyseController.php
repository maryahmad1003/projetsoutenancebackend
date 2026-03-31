<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAnalyse;
use Illuminate\Http\Request;

class DemandeAnalyseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/medecin/demandes-analyse",
     *     tags={"Médecin - Analyses"},
     *     summary="Lister les demandes d'analyse du médecin",
     *     description="Retourne la liste paginée des demandes d'analyse émises par le médecin connecté.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Liste des demandes paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;
        $demandes = DemandeAnalyse::where('medecin_id', $medecin->id)
            ->with(['patient.user', 'laboratoire', 'resultat'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($demandes);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/demandes-analyse",
     *     tags={"Médecin - Analyses"},
     *     summary="Créer une demande d'analyse",
     *     description="Envoie une demande d'analyse à un laboratoire pour un patient.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id","laboratoire_id","type_analyse"},
     *             @OA\Property(property="patient_id", type="integer", example=1),
     *             @OA\Property(property="laboratoire_id", type="integer", example=1),
     *             @OA\Property(property="type_analyse", type="string", example="Numération Formule Sanguine (NFS)"),
     *             @OA\Property(property="urgence", type="boolean", default=false),
     *             @OA\Property(property="notes", type="string", example="Patient fébrile depuis 3 jours")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Demande envoyée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Demande d'analyse envoyée"),
     *             @OA\Property(property="demande", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'laboratoire_id' => 'required|exists:laboratoires,id',
            'type_analyse' => 'required|string',
            'urgence' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $demande = DemandeAnalyse::create([
            'medecin_id' => $request->user()->medecin->id,
            'patient_id' => $request->patient_id,
            'laboratoire_id' => $request->laboratoire_id,
            'type_analyse' => $request->type_analyse,
            'urgence' => $request->urgence ?? false,
            'notes' => $request->notes,
            'statut' => 'envoyee',
        ]);

        return response()->json(['message' => 'Demande d\'analyse envoyée', 'demande' => $demande], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/demandes-analyse/{id}",
     *     tags={"Médecin - Analyses"},
     *     summary="Détails d'une demande d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la demande", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function show(string $id)
    {
        $demande = DemandeAnalyse::with(['patient.user', 'medecin.user', 'laboratoire', 'resultat'])->findOrFail($id);
        return response()->json($demande);
    }

    /**
     * @OA\Delete(
     *     path="/api/medecin/demandes-analyse/{id}",
     *     tags={"Médecin - Analyses"},
     *     summary="Supprimer une demande d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Demande supprimée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Demande supprimée"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function destroy(string $id)
    {
        DemandeAnalyse::findOrFail($id)->delete();
        return response()->json(['message' => 'Demande supprimée']);
    }
}
