<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\ResultatAnalyse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FhirService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected string $format;

    public function __construct()
    {
        $this->baseUrl = config('services.fhir.base_url');
        $this->apiKey = config('services.fhir.api_key');
        $this->format = config('services.fhir.format', 'json');
    }

    public function isEnabled(): bool
    {
        return !empty($this->baseUrl);
    }

    public function exportPatient(Patient $patient): array
    {
        $patient->load(['user', 'dossierMedical', 'carnetVaccination.vaccins']);

        $fhirPatient = [
            'resourceType' => 'Patient',
            'id' => (string) $patient->id,
            'identifier' => [
                [
                    'system' => 'urn:docsecur:patient',
                    'value' => $patient->num_dossier,
                ],
            ],
            'active' => true,
            'name' => [
                [
                    'use' => 'official',
                    'family' => $patient->user->nom ?? '',
                    'given' => [$patient->user->prenom ?? ''],
                ],
            ],
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => $patient->user->telephone ?? '',
                    'use' => 'mobile',
                ],
                [
                    'system' => 'email',
                    'value' => $patient->user->email ?? '',
                ],
            ],
            'gender' => $patient->sexe === 'M' ? 'male' : ($patient->sexe === 'F' ? 'female' : 'unknown'),
            'birthDate' => $patient->date_naissance?->format('Y-m-d'),
        ];

        return $fhirPatient;
    }

    public function exportConsultation(Consultation $consultation): array
    {
        $consultation->load(['dossierMedical.patient.user', 'medecin.user', 'prescriptions']);

        return [
            'resourceType' => 'Encounter',
            'id' => (string) $consultation->id,
            'status' => $this->mapStatutConsultation($consultation->statut ?? 'terminee'),
            'class' => [
                'code' => $consultation->type_consultation === 'teleconsultation' ? 'VR' : 'AMB',
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'display' => $consultation->type_consultation === 'teleconsultation' ? 'Virtual' : 'Ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/' . $consultation->dossierMedical?->patient?->id,
                'display' => $consultation->dossierMedical?->patient?->user?->prenom . ' ' . $consultation->dossierMedical?->patient?->user?->nom,
            ],
            'participant' => [
                [
                    'individual' => [
                        'reference' => 'Practitioner/' . $consultation->medecin?->id,
                        'display' => 'Dr. ' . $consultation->medecin?->user?->nom,
                    ],
                ],
            ],
            'period' => [
                'start' => $consultation->date?->format('Y-m-d\TH:i:sP'),
            ],
            'reasonCode' => [
                [
                    'text' => $consultation->motif ?? '',
                ],
            ],
        ];
    }

    public function exportObservation(Consultation $consultation): array
    {
        $observations = [];

        if ($consultation->tension) {
            $observations[] = $this->createObservation(
                $consultation->id . '-ta',
                'blood-pressure',
                'Blood Pressure',
                $consultation->tension,
                'mmHg'
            );
        }

        if ($consultation->glycemie) {
            $observations[] = $this->createObservation(
                $consultation->id . '-gly',
                'glucose',
                'Blood Glucose',
                $consultation->glycemie,
                'mmol/L'
            );
        }

        if ($consultation->poids) {
            $observations[] = $this->createObservation(
                $consultation->id . '-poids',
                'body-weight',
                'Body Weight',
                $consultation->poids,
                'kg'
            );
        }

        if ($consultation->temperature) {
            $observations[] = $this->createObservation(
                $consultation->id . '-temp',
                'body-temperature',
                'Body Temperature',
                $consultation->temperature,
                'Cel'
            );
        }

        if ($consultation->frequence_cardiaque) {
            $observations[] = $this->createObservation(
                $consultation->id . '-fc',
                'heart-rate',
                'Heart Rate',
                $consultation->frequence_cardiaque,
                '/min'
            );
        }

        return $observations;
    }

    protected function createObservation(string $id, string $code, string $display, float $value, string $unit): array
    {
        return [
            'resourceType' => 'Observation',
            'id' => $id,
            'status' => 'final',
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $code,
                        'display' => $display,
                    ],
                ],
            ],
            'effectiveDateTime' => now()->format('Y-m-d\TH:i:sP'),
            'valueQuantity' => [
                'value' => $value,
                'unit' => $unit,
                'system' => 'http://unitsofmeasure.org',
            ],
        ];
    }

    protected function mapStatutConsultation(?string $statut): string
    {
        return match ($statut) {
            'planifiee' => 'planned',
            'en_cours' => 'in-progress',
            'terminee' => 'finished',
            'annulee' => 'cancelled',
            default => 'unknown',
        };
    }

    public function sendToExternalSystem(array $resources): bool
    {
        if (!$this->isEnabled()) {
            Log::warning('[FHIR] External system not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/fhir+json',
                'Accept' => 'application/fhir+json',
            ])->post($this->baseUrl . '/Bundle', [
                'resourceType' => 'Bundle',
                'type' => 'transaction',
                'entry' => array_map(function ($resource) {
                    return [
                        'resource' => $resource,
                        'request' => [
                            'method' => 'POST',
                            'url' => $resource['resourceType'],
                        ],
                    ];
                }, $resources),
            ]);

            if ($response->successful()) {
                Log::info('[FHIR] Data sent successfully to external system');
                return true;
            }

            Log::error('[FHIR] Failed to send data: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('[FHIR] Error: ' . $e->getMessage());
            return false;
        }
    }

    public function receiveFromExternalSystem(string $resourceType, array $data): array
    {
        return match ($resourceType) {
            'Patient' => $this->importPatient($data),
            'Observation' => $this->importObservation($data),
            default => ['error' => 'Unsupported resource type'],
        };
    }

    protected function importPatient(array $data): array
    {
        $identifier = $data['identifier'][0]['value'] ?? null;

        $patient = Patient::where('num_dossier', $identifier)->first();

        if ($patient) {
            return ['status' => 'updated', 'patient_id' => $patient->id];
        }

        return ['status' => 'received', 'data' => $data];
    }

    protected function importObservation(array $data): array
    {
        return ['status' => 'received', 'data' => $data];
    }
}
