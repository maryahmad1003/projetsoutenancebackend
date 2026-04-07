<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Medecin;
use App\Models\RendezVous;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HoraireController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function getDisponibilites(Request $request, int $medecinId): JsonResponse
    {
        $medecin = Medecin::with(['user', 'centreSante'])->findOrFail($medecinId);

        if (!$medecin->accepte_rdv_en_ligne) {
            return response()->json([
                'message' => 'Ce médecin n\'accepte pas les rendez-vous en ligne',
                'accepte_rdv_en_ligne' => false,
            ], 403);
        }

        $creneaux = $medecin->getProchainCreneauxDisponibles(20);

        $rdvToday = $medecin->rendezVous()
            ->whereDate('date_heure', now()->toDateString())
            ->whereIn('statut', ['en_attente', 'confirme'])
            ->count();

        return response()->json([
            'medecin' => [
                'id' => $medecin->id,
                'nom' => 'Dr. ' . $medecin->user->nom,
                'prenom' => $medecin->user->prenom,
                'specialite' => $medecin->specialite,
                'centre' => $medecin->centreSante?->nom,
            ],
            'horaires' => $medecin->horaires ?? $medecin->getDefaultHoraires(),
            'creneaux' => $creneaux,
            'rdv_aujourd_hui' => $rdvToday,
        ]);
    }

    public function definirHoraires(Request $request): JsonResponse
    {
        $request->validate([
            'horaires' => 'required|array',
            'horaires.*.debut' => 'nullable|date_format:H:i',
            'horaires.*.fin' => 'nullable|date_format:H:i',
            'horaires.*.active' => 'nullable|boolean',
        ]);

        $medecin = $request->user()->medecin;
        
        if (!$medecin) {
            return response()->json(['message' => 'Profil médecin non trouvé'], 404);
        }

        $medecin->horaires = $request->horaires;
        $medecin->save();

        Log::info('[HORAIRE] Horaires mis à jour pour le médecin ' . $medecin->id);

        return response()->json([
            'success' => true,
            'message' => 'Horaires enregistrés',
            'horaires' => $medecin->horaires,
        ]);
    }

    public function getRendezVousMedecin(Request $request): JsonResponse
    {
        $medecin = $request->user()->medecin;
        
        if (!$medecin) {
            return response()->json(['message' => 'Profil médecin non trouvé'], 404);
        }

        $rdv = $medecin->rendezVous()
            ->with(['patient.user'])
            ->orderBy('date_heure', 'desc')
            ->paginate(20);

        return response()->json($rdv);
    }

    public function confirmerRendezVous(Request $request, int $id): JsonResponse
    {
        $rdv = RendezVous::findOrFail($id);
        
        if ($rdv->medecin_id !== $request->user()->medecin?->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $rdv->statut = 'confirme';
        $rdv->save();

        $patientUser = $rdv->patient->user;
        $this->notificationService->envoyer(
            $patientUser,
            'rendez_vous',
            'Votre rendez-vous du ' . 
                \Carbon\Carbon::parse($rdv->date_heure)->format('d/m/Y à H:i') . 
                ' a été confirmé par le médecin.',
            'application'
        );

        Log::info('[RDV] Rendez-vous ' . $id . ' confirmé');

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous confirmé',
            'rdv' => $rdv,
        ]);
    }

    public function definirDateHeure(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date_heure' => 'required|date|after:now',
        ]);

        $rdv = RendezVous::with(['patient.user'])->findOrFail($id);
        
        if ($rdv->medecin_id !== $request->user()->medecin?->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($rdv->type !== 'teleconsultation') {
            return response()->json(['message' => 'Cette fonctionnalité est réservés aux téléconsultations'], 400);
        }

        $medecin = $request->user()->medecin;
        
        if (!$medecin->isAvailableOn($request->date_heure)) {
            return response()->json([
                'message' => 'Ce créneau horaire n\'est pas disponible.',
                'disponible' => false,
            ], 422);
        }

        $rdv->date_heure = $request->date_heure;
        $rdv->save();

        $patientUser = $rdv->patient->user;
        $this->notificationService->envoyer(
            $patientUser,
            'rendez_vous',
            'Votre téléconsultation a été programmée pour le ' . 
                \Carbon\Carbon::parse($rdv->date_heure)->format('d/m/Y à H:i') . 
                '. Merci de confirmer ce rendez-vous.',
            'application'
        );

        Log::info('[RDV] Date/heure définie pour téléconsultation ' . $id . ' par médecin ' . $medecin->id);

        return response()->json([
            'success' => true,
            'message' => 'Date et heure définies. Le patient sera notifié pour confirmation.',
            'rdv' => $rdv,
        ]);
    }

    public function refuserRendezVous(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motif_refus' => 'nullable|string|max:500',
        ]);

        $rdv = RendezVous::findOrFail($id);
        
        if ($rdv->medecin_id !== $request->user()->medecin?->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $rdv->statut = 'annule';
        $rdv->notes = $request->motif_refus;
        $rdv->save();

        $patientUser = $rdv->patient->user;
        $this->notificationService->envoyer(
            $patientUser,
            'rendez_vous',
            'Votre rendez-vous du ' . 
                \Carbon\Carbon::parse($rdv->date_heure)->format('d/m/Y à H:i') . 
                ' a été annulé. Motif: ' . ($request->motif_refus ?? 'Non spécifié'),
            'application'
        );

        Log::info('[RDV] Rendez-vous ' . $id . ' annulé');

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous annulé',
            'rdv' => $rdv,
        ]);
    }
}