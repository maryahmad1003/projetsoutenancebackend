<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\RendezVous;
use Illuminate\Http\Request;

class RendezVousController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/patient/rendez-vous",
     *     tags={"Patient - Rendez-vous"},
     *     summary="Lister les rendez-vous du patient connecté",
     *     description="Retourne la liste paginée des rendez-vous du patient authentifié, triés par date décroissante.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Liste des rendez-vous", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $patient = $request->user()->patient;
        $rdvs = RendezVous::where('patient_id', $patient->id)
            ->with(['medecin.user', 'medecin.centreSante'])
            ->orderBy('date_heure', 'desc')
            ->paginate(20);

        return response()->json($rdvs);
    }

    /**
     * @OA\Post(
     *     path="/api/patient/rendez-vous",
     *     tags={"Patient - Rendez-vous"},
     *     summary="Prendre un rendez-vous",
     *     description="Crée un nouveau rendez-vous avec un médecin. La date doit être dans le futur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"medecin_id","date_heure","motif"},
     *             @OA\Property(property="medecin_id", type="integer", example=1),
     *             @OA\Property(property="date_heure", type="string", format="date-time", example="2026-04-15T09:30:00"),
     *             @OA\Property(property="motif", type="string", example="Consultation générale"),
     *             @OA\Property(property="duree", type="integer", default=30, description="Durée en minutes"),
     *             @OA\Property(property="type", type="string", enum={"consultation","suivi","urgence","teleconsultation"}, default="consultation")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Rendez-vous créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rendez-vous créé"),
     *             @OA\Property(property="rendez_vous", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'medecin_id' => 'required|exists:medecins,id',
            'date_heure' => 'required|date|after:now',
            'motif' => 'required|string',
            'type' => 'nullable|in:consultation,suivi,urgence,teleconsultation',
        ]);

        $rdv = RendezVous::create([
            'patient_id' => $request->user()->patient->id,
            'medecin_id' => $request->medecin_id,
            'date_heure' => $request->date_heure,
            'duree' => $request->duree ?? 30,
            'motif' => $request->motif,
            'statut' => 'en_attente',
            'type' => $request->type ?? 'consultation',
        ]);

        return response()->json(['message' => 'Rendez-vous créé', 'rendez_vous' => $rdv->load('medecin.user')], 201);
    }

    public function show(string $id)
    {
        $rdv = RendezVous::with(['medecin.user', 'medecin.centreSante', 'patient.user'])->findOrFail($id);
        return response()->json($rdv);
    }

    /**
     * @OA\Put(
     *     path="/api/patient/rendez-vous/{id}/modifier",
     *     tags={"Patient - Rendez-vous"},
     *     summary="Modifier un rendez-vous",
     *     description="Modifie la date, le motif, le statut ou le type d'un rendez-vous.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="date_heure", type="string", format="date-time"),
     *             @OA\Property(property="motif", type="string"),
     *             @OA\Property(property="statut", type="string", enum={"en_attente","confirme","annule"}),
     *             @OA\Property(property="type", type="string", enum={"consultation","suivi","urgence","teleconsultation"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rendez-vous modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rendez-vous modifié"),
     *             @OA\Property(property="rendez_vous", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function modifier(Request $request, string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update($request->only(['date_heure', 'motif', 'statut', 'type']));

        return response()->json(['message' => 'Rendez-vous modifié', 'rendez_vous' => $rdv]);
    }

    public function update(Request $request, string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update($request->only(['date_heure', 'motif', 'statut', 'type']));

        return response()->json(['message' => 'Rendez-vous modifié', 'rendez_vous' => $rdv]);
    }

    /**
     * @OA\Put(
     *     path="/api/patient/rendez-vous/{id}/annuler",
     *     tags={"Patient - Rendez-vous"},
     *     summary="Annuler un rendez-vous",
     *     description="Annule un rendez-vous en passant son statut à 'annule'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rendez-vous annulé",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Rendez-vous annulé"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function annuler(string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update(['statut' => 'annule']);

        return response()->json(['message' => 'Rendez-vous annulé']);
    }

    public function destroy(string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update(['statut' => 'annule']);

        return response()->json(['message' => 'Rendez-vous annulé']);
    }
}
