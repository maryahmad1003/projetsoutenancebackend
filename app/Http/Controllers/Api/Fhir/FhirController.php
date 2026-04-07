<?php

namespace App\Http\Controllers\Api\Fhir;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Consultation;
use App\Services\FhirService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FhirController extends Controller
{
    public function __construct(private FhirService $fhirService) {}

    public function exportPatient(Request $request, int $patientId): JsonResponse
    {
        $patient = Patient::with(['user', 'dossierMedical', 'carnetVaccination.vaccins'])
            ->findOrFail($patientId);

        $fhirPatient = $this->fhirService->exportPatient($patient);

        return response()->json($fhirPatient);
    }

    public function exportConsultation(Request $request, int $consultationId): JsonResponse
    {
        $consultation = Consultation::with([
            'dossierMedical.patient.user',
            'medecin.user',
            'prescriptions.medicaments'
        ])->findOrFail($consultationId);

        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'entry' => [],
        ];

        $bundle['entry'][] = [
            'resource' => $this->fhirService->exportConsultation($consultation),
        ];

        foreach ($this->fhirService->exportObservation($consultation) as $observation) {
            $bundle['entry'][] = ['resource' => $observation];
        }

        return response()->json($bundle);
    }

    public function exportAllPatients(Request $request): JsonResponse
    {
        $patients = Patient::with(['user', 'dossierMedical'])->get();

        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'total' => $patients->count(),
            'entry' => [],
        ];

        foreach ($patients as $patient) {
            $bundle['entry'][] = [
                'resource' => $this->fhirService->exportPatient($patient),
            ];
        }

        return response()->json($bundle);
    }

    public function sendToExternal(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consultation_ids' => 'nullable|array',
            'consultation_ids.*' => 'exists:consultations,id',
        ]);

        $patient = Patient::with(['user', 'dossierMedical'])->findOrFail($request->patient_id);

        $resources = [];
        $resources[] = $this->fhirService->exportPatient($patient);

        if ($request->has('consultation_ids')) {
            $consultations = Consultation::with(['medecin.user', 'dossierMedical'])
                ->whereIn('id', $request->consultation_ids)
                ->get();

            foreach ($consultations as $consultation) {
                $resources[] = $this->fhirService->exportConsultation($consultation);
                foreach ($this->fhirService->exportObservation($consultation) as $observation) {
                    $resources[] = $observation;
                }
            }
        }

        $success = $this->fhirService->sendToExternalSystem($resources);

        return response()->json([
            'success' => $success,
            'resources_count' => count($resources),
            'message' => $success
                ? 'Donnees exportees vers le systeme externe'
                : 'Echec de l\'export vers le systeme externe',
        ]);
    }

    public function receive(Request $request): JsonResponse
    {
        $request->validate([
            'resourceType' => 'required|string',
            'data' => 'required|array',
        ]);

        $result = $this->fhirService->receiveFromExternalSystem(
            $request->resourceType,
            $request->data
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => $this->fhirService->isEnabled(),
            'format' => 'FHIR R4',
            'supported_resources' => ['Patient', 'Encounter', 'Observation', 'DiagnosticReport'],
        ]);
    }
}
