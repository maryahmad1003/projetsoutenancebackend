<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\RendezVous;
use Illuminate\Http\Request;

class RendezVousController extends Controller
{
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

    public function update(Request $request, string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update($request->only(['date_heure', 'motif', 'statut', 'type']));

        return response()->json(['message' => 'Rendez-vous modifié', 'rendez_vous' => $rdv]);
    }

    public function destroy(string $id)
    {
        $rdv = RendezVous::findOrFail($id);
        $rdv->update(['statut' => 'annule']);

        return response()->json(['message' => 'Rendez-vous annulé']);
    }
}