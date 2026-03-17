<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Teleconsultation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeleconsultationController extends Controller
{
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;
        $teleconsultations = Teleconsultation::where('medecin_id', $medecin->id)
            ->with(['patient.user', 'consultation'])
            ->orderBy('date_debut', 'desc')
            ->paginate(20);

        return response()->json($teleconsultations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'date_debut' => 'required|date',
        ]);

        $lienVideo = 'https://meet.jit.si/docsecur-' . Str::random(12);

        $teleconsultation = Teleconsultation::create([
            'medecin_id' => $request->user()->medecin->id,
            'patient_id' => $request->patient_id,
            'consultation_id' => $request->consultation_id,
            'date_debut' => $request->date_debut,
            'lien_video' => $lienVideo,
            'statut' => 'planifiee',
        ]);

        return response()->json([
            'message' => 'Téléconsultation planifiée',
            'teleconsultation' => $teleconsultation,
            'lien_video' => $lienVideo,
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

    public function demarrer(string $id)
    {
        $teleconsultation = Teleconsultation::findOrFail($id);
        $teleconsultation->update(['statut' => 'en_cours', 'date_debut' => now()]);

        return response()->json(['message' => 'Téléconsultation démarrée', 'lien_video' => $teleconsultation->lien_video]);
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