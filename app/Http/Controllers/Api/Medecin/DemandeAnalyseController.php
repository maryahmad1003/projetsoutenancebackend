<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAnalyse;
use Illuminate\Http\Request;

class DemandeAnalyseController extends Controller
{
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;
        $demandes = DemandeAnalyse::where('medecin_id', $medecin->id)
            ->with(['patient.user', 'laboratoire', 'resultat'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($demandes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'laboratoire_id' => 'required|exists:laboratoires,id',
            'type_analyse' => 'required|string',
            'urgence' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $demande = DemandeAnalyse::create([
            'medecin_id' => $request->user()->medecin->id,
            'patient_id' => $request->patient_id,
            'laboratoire_id' => $request->laboratoire_id,
            'type_analyse' => $request->type_analyse,
            'urgence' => $request->urgence ?? false,
            'notes' => $request->notes,
            'statut' => 'envoyee',
        ]);

        return response()->json(['message' => 'Demande d\'analyse envoyée', 'demande' => $demande], 201);
    }

    public function show(string $id)
    {
        $demande = DemandeAnalyse::with(['patient.user', 'medecin.user', 'laboratoire', 'resultat'])->findOrFail($id);
        return response()->json($demande);
    }

    public function destroy(string $id)
    {
        DemandeAnalyse::findOrFail($id)->delete();
        return response()->json(['message' => 'Demande supprimée']);
    }
}