<?php

namespace App\Http\Controllers\Api\Laborantin;

use App\Http\Controllers\Controller;
use App\Models\ResultatAnalyse;
use App\Models\DemandeAnalyse;
use App\Models\Notification;
use Illuminate\Http\Request;

class ResultatAnalyseController extends Controller
{
    public function index(Request $request)
    {
        $laborantin = $request->user()->laborantin;
        $resultats = ResultatAnalyse::where('laborantin_id', $laborantin->id)
            ->with(['demandeAnalyse.patient.user', 'demandeAnalyse.medecin.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($resultats);
    }

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

    public function update(Request $request, string $id)
    {
        $resultat = ResultatAnalyse::findOrFail($id);
        $resultat->update($request->all());
        return response()->json(['message' => 'Résultat mis à jour', 'resultat' => $resultat]);
    }

    public function destroy(string $id)
    {
        ResultatAnalyse::findOrFail($id)->delete();
        return response()->json(['message' => 'Résultat supprimé']);
    }
}