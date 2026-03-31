<?php

namespace App\Http\Controllers\Api\Laborantin;

use App\Http\Controllers\Controller;
use App\Models\ResultatAnalyse;
use App\Models\DemandeAnalyse;
use App\Models\Notification;
use Illuminate\Http\Request;

class ResultatAnalyseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/laborantin/resultats",
     *     tags={"Laborantin - Résultats"},
     *     summary="Lister les résultats d'analyse du laborantin",
     *     description="Retourne la liste paginée des résultats d'analyse saisis par le laborantin connecté.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Liste des résultats", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $laborantin = $request->user()->laborantin;
        $resultats = ResultatAnalyse::where('laborantin_id', $laborantin->id)
            ->with(['demandeAnalyse.patient.user', 'demandeAnalyse.medecin.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($resultats);
    }

    /**
     * @OA\Post(
     *     path="/api/laborantin/resultats",
     *     tags={"Laborantin - Résultats"},
     *     summary="Saisir un résultat d'analyse",
     *     description="Enregistre le résultat d'une analyse médicale. La demande est automatiquement marquée comme terminée et le médecin est notifié.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"demande_analyse_id","type_analyse","resultats"},
     *             @OA\Property(property="demande_analyse_id", type="integer", example=1),
     *             @OA\Property(property="type_analyse", type="string", example="Numération Formule Sanguine (NFS)"),
     *             @OA\Property(property="resultats", type="string", example="Globules blancs: 8000/µL\nHémoglobine: 14 g/dL"),
     *             @OA\Property(property="valeur_normale", type="string", example="Globules blancs: 4000-10000/µL"),
     *             @OA\Property(property="interpretation", type="string", example="Résultats normaux"),
     *             @OA\Property(property="date_prelevement", type="string", format="date", example="2026-03-28")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Résultat enregistré et médecin notifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Résultat envoyé, médecin notifié"),
     *             @OA\Property(property="resultat", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'demande_analyse_id' => 'required|exists:demandes_analyses,id',
            'type_analyse' => 'required|string',
            'resultats' => 'required|string',
            'valeur_normale' => 'nullable|string',
            'interpretation' => 'nullable|string',
            'date_prelevement' => 'nullable|date',
        ]);

        $demande = DemandeAnalyse::with('patient.dossierMedical', 'medecin.user')->findOrFail($request->demande_analyse_id);

        $resultat = ResultatAnalyse::create([
            'demande_analyse_id' => $request->demande_analyse_id,
            'dossier_medical_id' => $demande->patient->dossierMedical->id,
            'laborantin_id' => $request->user()->laborantin->id,
            'type_analyse' => $request->type_analyse,
            'date_prelevement' => $request->date_prelevement,
            'date_resultat' => now(),
            'resultats' => $request->resultats,
            'valeur_normale' => $request->valeur_normale,
            'interpretation' => $request->interpretation,
            'statut' => 'disponible',
        ]);

        $demande->update(['statut' => 'terminee']);

        Notification::create([
            'user_id' => $demande->medecin->user->id,
            'type' => 'resultat_dispo',
            'message' => 'Les résultats d\'analyse de votre patient sont disponibles.',
            'canal' => 'sms',
            'date_envoi' => now(),
        ]);

        return response()->json(['message' => 'Résultat envoyé, médecin notifié', 'resultat' => $resultat], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/laborantin/resultats/{id}",
     *     tags={"Laborantin - Résultats"},
     *     summary="Détails d'un résultat d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails du résultat", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function show(string $id)
    {
        $resultat = ResultatAnalyse::with(['demandeAnalyse.patient.user', 'demandeAnalyse.medecin.user', 'laborantin.user'])->findOrFail($id);
        return response()->json($resultat);
    }

    public function mesResultats(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = $patient->dossierMedical;

        $resultats = ResultatAnalyse::where('dossier_medical_id', $dossier->id)
            ->with(['demandeAnalyse.medecin.user', 'laborantin.user'])
            ->orderBy('date_resultat', 'desc')
            ->get();

        return response()->json($resultats);
    }

    /**
     * @OA\Put(
     *     path="/api/laborantin/resultats/{id}",
     *     tags={"Laborantin - Résultats"},
     *     summary="Modifier un résultat d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="resultats", type="string"),
     *             @OA\Property(property="valeur_normale", type="string"),
     *             @OA\Property(property="interpretation", type="string"),
     *             @OA\Property(property="statut", type="string", enum={"disponible","envoye"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Résultat mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Résultat mis à jour"),
     *             @OA\Property(property="resultat", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function update(Request $request, string $id)
    {
        $resultat = ResultatAnalyse::findOrFail($id);
        $resultat->update($request->all());
        return response()->json(['message' => 'Résultat mis à jour', 'resultat' => $resultat]);
    }

    /**
     * @OA\Delete(
     *     path="/api/laborantin/resultats/{id}",
     *     tags={"Laborantin - Résultats"},
     *     summary="Supprimer un résultat d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Résultat supprimé",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Résultat supprimé"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function destroy(string $id)
    {
        ResultatAnalyse::findOrFail($id)->delete();
        return response()->json(['message' => 'Résultat supprimé']);
    }

    /**
     * @OA\Post(
     *     path="/api/laborantin/resultats/{id}/envoyer",
     *     tags={"Laborantin - Résultats"},
     *     summary="Envoyer un résultat au médecin",
     *     description="Marque le résultat comme envoyé et notifie le médecin.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Résultat envoyé",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Résultat envoyé au médecin"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function envoyer(Request $request, string $id)
    {
        $resultat = ResultatAnalyse::with(['demandeAnalyse.medecin.user'])->findOrFail($id);
        $resultat->update(['statut' => 'envoye']);

        Notification::create([
            'user_id' => $resultat->demandeAnalyse->medecin->user->id,
            'type' => 'resultat_dispo',
            'message' => 'Les résultats d\'analyse de votre patient ont été mis à jour.',
            'canal' => 'sms',
            'date_envoi' => now(),
        ]);

        return response()->json(['message' => 'Résultat envoyé au médecin']);
    }
}
