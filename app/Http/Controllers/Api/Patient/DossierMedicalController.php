<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\DossierMedical;
use Illuminate\Http\Request;

class DossierMedicalController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::with(['user', 'dossierMedical'])->paginate(20);
        return response()->json($patients);
    }

    public function show(string $id)
    {
        $patient = Patient::with([
            'user',
            'dossierMedical.consultations.medecin.user',
            'dossierMedical.consultations.prescriptions.medicaments',
            'dossierMedical.resultatsAnalyses',
            'carnetVaccination.vaccins',
            'rendezVous.medecin.user',
        ])->findOrFail($id);

        return response()->json($patient);
    }

    public function monDossier(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = DossierMedical::where('patient_id', $patient->id)
            ->with(['consultations.medecin.user', 'consultations.prescriptions.medicaments', 'resultatsAnalyses'])
            ->first();

        return response()->json($dossier);
    }

    public function monHistorique(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = $patient->dossierMedical;

        $consultations = $dossier->consultations()
            ->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($consultations);
    }

    public function historique(string $id)
    {
        $patient = Patient::findOrFail($id);
        $dossier = $patient->dossierMedical;

        $consultations = $dossier->consultations()
            ->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($consultations);
    }

    public function update(Request $request, string $id)
    {
        $dossier = DossierMedical::findOrFail($id);
        $dossier->update($request->only(['antecedents', 'allergies', 'notes_generales']));

        return response()->json(['message' => 'Dossier mis à jour', 'dossier' => $dossier]);
    }
}