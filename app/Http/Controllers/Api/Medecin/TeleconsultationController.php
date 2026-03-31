<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Teleconsultation;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeleconsultationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/medecin/teleconsultations",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Lister les téléconsultations",
     *     description="Retourne la liste paginée des téléconsultations du médecin connecté.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="statut", in="query", description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"planifiee","en_cours","terminee"})
     *     ),
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom du patient", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Liste paginée des téléconsultations", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;

        $query = Teleconsultation::where('medecin_id', $medecin->id)
            ->with(['patient.user', 'consultation']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('date_debut', 'desc')->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/teleconsultations",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Planifier une téléconsultation",
     *     description="Crée une téléconsultation avec un lien vidéo Jitsi généré automatiquement. Le patient est notifié.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id","date_debut"},
     *             @OA\Property(property="patient_id", type="integer", example=1),
     *             @OA\Property(property="consultation_id", type="integer", nullable=true),
     *             @OA\Property(property="date_debut", type="string", format="date-time", example="2026-04-10T10:00:00"),
     *             @OA\Property(property="motif", type="string", example="Suivi traitement paludisme")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Téléconsultation planifiée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Téléconsultation planifiée"),
     *             @OA\Property(property="teleconsultation", type="object"),
     *             @OA\Property(property="lien_video", type="string", example="https://meet.jit.si/docsecur-abc123"),
     *             @OA\Property(property="room_name", type="string", example="docsecur-abc123")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'date_debut'      => 'required|date',
            'motif'           => 'nullable|string|max:500',
        ]);

        $medecin   = $request->user()->medecin;
        $roomName  = 'docsecur-' . Str::random(16);
        $lienVideo = 'https://meet.jit.si/' . $roomName;

        $teleconsultation = Teleconsultation::create([
            'medecin_id'      => $medecin->id,
            'patient_id'      => $request->patient_id,
            'consultation_id' => $request->consultation_id,
            'date_debut'      => $request->date_debut,
            'lien_video'      => $lienVideo,
            'statut'          => 'planifiee',
        ]);

        $teleconsultation->load('patient');
        Notification::create([
            'user_id'    => $teleconsultation->patient->user_id,
            'type'       => 'teleconsultation',
            'message'    => 'Une téléconsultation a été planifiée le '
                            . \Carbon\Carbon::parse($request->date_debut)->format('d/m/Y à H:i')
                            . ' par Dr. ' . $request->user()->nom . '. Lien : ' . $lienVideo,
            'canal'      => 'application',
            'date_envoi' => now(),
        ]);

        return response()->json([
            'message'          => 'Téléconsultation planifiée',
            'teleconsultation' => $teleconsultation->load('patient.user'),
            'lien_video'       => $lienVideo,
            'room_name'        => $roomName,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/teleconsultations/{id}",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Détails d'une téléconsultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la téléconsultation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function show(string $id)
    {
        $teleconsultation = Teleconsultation::with(['medecin.user', 'patient.user', 'consultation'])->findOrFail($id);
        return response()->json($teleconsultation);
    }

    /**
     * @OA\Put(
     *     path="/api/medecin/teleconsultations/{id}",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Modifier une téléconsultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="statut", type="string", enum={"planifiee","en_cours","terminee"}),
     *             @OA\Property(property="date_fin", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Téléconsultation mise à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="teleconsultation", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function update(Request $request, string $id)
    {
        $teleconsultation = Teleconsultation::findOrFail($id);
        $teleconsultation->update($request->only(['statut', 'date_fin']));

        return response()->json(['message' => 'Téléconsultation mise à jour', 'teleconsultation' => $teleconsultation]);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/teleconsultations/{id}/demarrer",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Démarrer une téléconsultation",
     *     description="Passe le statut en 'en_cours' et notifie le patient avec le lien vidéo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Téléconsultation démarrée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Téléconsultation démarrée"),
     *             @OA\Property(property="lien_video", type="string"),
     *             @OA\Property(property="room_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function demarrer(Request $request, string $id)
    {
        $teleconsultation = Teleconsultation::with('patient')->findOrFail($id);
        $teleconsultation->update(['statut' => 'en_cours']);

        Notification::create([
            'user_id'    => $teleconsultation->patient->user_id,
            'type'       => 'teleconsultation',
            'message'    => 'Votre téléconsultation vient de démarrer. Rejoignez maintenant : ' . $teleconsultation->lien_video,
            'canal'      => 'application',
            'date_envoi' => now(),
        ]);

        return response()->json([
            'message'    => 'Téléconsultation démarrée',
            'lien_video' => $teleconsultation->lien_video,
            'room_name'  => basename(parse_url($teleconsultation->lien_video, PHP_URL_PATH)),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/teleconsultations/{id}/terminer",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Terminer une téléconsultation",
     *     description="Passe le statut en 'terminee' et enregistre la date de fin.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Téléconsultation terminée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Téléconsultation terminée"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function terminer(string $id)
    {
        $teleconsultation = Teleconsultation::findOrFail($id);
        $teleconsultation->update(['statut' => 'terminee', 'date_fin' => now()]);

        return response()->json(['message' => 'Téléconsultation terminée']);
    }

    /**
     * @OA\Delete(
     *     path="/api/medecin/teleconsultations/{id}",
     *     tags={"Médecin - Téléconsultations"},
     *     summary="Supprimer une téléconsultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supprimée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Téléconsultation supprimée"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function destroy(string $id)
    {
        Teleconsultation::findOrFail($id)->delete();
        return response()->json(['message' => 'Téléconsultation supprimée']);
    }
}
