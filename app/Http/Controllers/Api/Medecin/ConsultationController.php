<?php
namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DossierMedical;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;
        $consultations = Consultation::where('medecin_id', $medecin->id)
            ->with(['dossierMedical.patient.user', 'prescriptions'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json($consultations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dossier_medical_id' => 'required|exists:dossiers_medicaux,id',
            'motif' => 'required|string',
            'diagnostic' => 'nullable|string',
            'notes' => 'nullable|string',
            'tension' => 'nullable|string',
            'poids' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'frequence_cardiaque' => 'nullable|integer',
            'glycemie' => 'nullable|numeric',
            'type_consultation' => 'nullable|in:presentiel,teleconsultation',
        ]);

        $medecin = $request->user()->medecin;

        $consultation = Consultation::create([
            'dossier_medical_id' => $request->dossier_medical_id,
            'medecin_id' => $medecin->id,
            'date' => now(),
            'motif' => $request->motif,
            'diagnostic' => $request->diagnostic,
            'notes' => $request->notes,
            'tension' => $request->tension,
            'poids' => $request->poids,
            'temperature' => $request->temperature,
            'frequence_cardiaque' => $request->frequence_cardiaque,
            'glycemie' => $request->glycemie,
            'type_consultation' => $request->type_consultation ?? 'presentiel',
        ]);

        return response()->json([
            'message' => 'Consultation enregistrée avec succès',
            'consultation' => $consultation->load(['dossierMedical.patient.user'])
        ], 201);
    }

    public function show(string $id)
    {
        $consultation = Consultation::with([
            'dossierMedical.patient.user',
            'medecin.user',
            'prescriptions.medicaments',
            'teleconsultation'
        ])->findOrFail($id);

        return response()->json($consultation);
    }

    public function update(Request $request, string $id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->update($request->all());

        return response()->json([
            'message' => 'Consultation mise à jour',
            'consultation' => $consultation
        ]);
    }

    public function destroy(string $id)
    {
        Consultation::findOrFail($id)->delete();

        return response()->json(['message' => 'Consultation supprimée']);
    }
}