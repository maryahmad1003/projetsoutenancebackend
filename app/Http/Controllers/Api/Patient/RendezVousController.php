<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\RendezVous;
use App\Models\Medecin;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RendezVousController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $patient = $request->user()->patient;
        $rdvs = RendezVous::where('patient_id', $patient->id)
            ->with(['medecin.user', 'medecin.centreSante'])
            ->orderBy('date_heure', 'desc')
            ->paginate(20);

        return response()->json($rdvs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'medecin_id' => 'required|exists:medecins,id',
            'date_heure' => 'required|date|after:now',
            'motif' => 'required|string',
            'type' => 'nullable|in:presentiel,teleconsultation,consultation,suivi,urgence',
        ]);

        $medecin = Medecin::with('user')->findOrFail($request->medecin_id);
        $type = $request->type ?? 'presentiel';
        if ($type === 'presentiel') {
            $type = 'consultation';
        }
        $dateHeure = $request->date_heure;

        if ($type !== 'teleconsultation') {
            if (!$medecin->isAvailableOn($dateHeure)) {
                return response()->json([
                    'message' => 'Ce créneau horaire n\'est pas disponible. Veuillez choisir un autre horaire.',
                    'disponible' => false,
                ], 422);
            }
        }

        $rdv = RendezVous::create([
            'patient_id' => $request->user()->patient->id,
            'medecin_id' => $request->medecin_id,
            'date_heure' => $dateHeure,
            'duree' => $request->duree ?? 30,
            'motif' => $request->motif,
            'statut' => 'en_attente',
            'type' => $type,
        ]);

        $medecinUser = $medecin->user;
        $patientUser = $request->user();
        
        if ($type === 'teleconsultation') {
            $this->notificationService->envoyer(
                $medecinUser,
                'rendez_vous',
                'Nouvelle demande de téléconsultation de ' . $patientUser->prenom . ' ' . $patientUser->nom . 
                '. Motif: ' . $request->motif . '. Veuillez définir la date et l\'heure.',
                'application'
            );
        } else {
            $this->notificationService->envoyer(
                $medecinUser,
                'rendez_vous',
                'Nouveau rendez-vous de ' . $patientUser->prenom . ' ' . $patientUser->nom . 
                ' le ' . \Carbon\Carbon::parse($dateHeure)->format('d/m/Y à H:i') . 
                '. Motif: ' . $request->motif,
                'application'
            );
        }

        Log::info('[RDV] Nouveau rendez-vous créé pour médecin ' . $medecin->id . ' par patient ' . $request->user()->patient->id . ' (type: ' . $type . ')');

        return response()->json([
            'message' => $type === 'teleconsultation' ? 'Demande de téléconsultation envoyée au médecin.' : 'Demande de rendez-vous envoyée au médecin.',
            'rendez_vous' => $rdv->load('medecin.user'),
            'statut' => 'en_attente',
        ], 201);
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

    public function confirmer(string $id)
    {
        $rdv = RendezVous::with(['medecin.user'])->findOrFail($id);
        $patient = auth()->user()->patient;

        if ($rdv->patient_id !== $patient->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$rdv->date_heure) {
            return response()->json(['message' => 'Aucune date définie pour ce rendez-vous'], 400);
        }

        if ($rdv->statut !== 'en_attente') {
            return response()->json(['message' => 'Ce rendez-vous ne peut pas être confirmé'], 400);
        }

        $rdv->update(['statut' => 'confirme']);

        $medecinUser = $rdv->medecin->user;
        $patientUser = auth()->user();
        
        $this->notificationService->envoyer(
            $medecinUser,
            'rendez_vous',
            'Le patient ' . $patientUser->prenom . ' ' . $patientUser->nom . 
            ' a confirmé la téléconsultation du ' . 
            \Carbon\Carbon::parse($rdv->date_heure)->format('d/m/Y à H:i'),
            'application'
        );

        Log::info('[RDV] Rendez-vous ' . $id . ' confirmé par le patient ' . $patient->id);

        return response()->json([
            'message' => 'Rendez-vous confirmé',
            'rendez_vous' => $rdv,
        ]);
    }

    public function destroy(string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update(['statut' => 'annule']);

        return response()->json(['message' => 'Rendez-vous annulé']);
    }
}
