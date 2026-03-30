<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Teleconsultation;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeleconsultationController extends Controller
{
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

        // Notifier le patient
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

    public function show(string $id)
    {
        $teleconsultation = Teleconsultation::with(['medecin.user', 'patient.user', 'consultation'])->findOrFail($id);
        return response()->json($teleconsultation);
    }

    public function update(Request $request, string $id)
    {
        $teleconsultation = Teleconsultation::findOrFail($id);
        $teleconsultation->update($request->only(['statut', 'date_fin']));

        return response()->json(['message' => 'Téléconsultation mise à jour', 'teleconsultation' => $teleconsultation]);
    }

    public function demarrer(Request $request, string $id)
    {
        $teleconsultation = Teleconsultation::with('patient')->findOrFail($id);
        $teleconsultation->update(['statut' => 'en_cours']);

        // Notifier le patient que la session est prête
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

    public function terminer(string $id)
    {
        $teleconsultation = Teleconsultation::findOrFail($id);
        $teleconsultation->update(['statut' => 'terminee', 'date_fin' => now()]);

        return response()->json(['message' => 'Téléconsultation terminée']);
    }

    public function destroy(string $id)
    {
        Teleconsultation::findOrFail($id)->delete();
        return response()->json(['message' => 'Téléconsultation supprimée']);
    }
}