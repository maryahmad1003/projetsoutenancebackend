<?php

namespace App\Repositories\Eloquent;

use App\Models\Patient;
use App\Models\Consultation;
use App\Repositories\Interfaces\PatientRepositoryInterface;

class PatientRepository implements PatientRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 15)
    {
        $query = Patient::with('user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', fn ($q) =>
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
            )->orWhere('num_dossier', 'like', "%{$search}%");
        }

        if (!empty($filters['sexe'])) {
            $query->where('sexe', $filters['sexe']);
        }

        if (!empty($filters['groupe_sanguin'])) {
            $query->where('groupe_sanguin', $filters['groupe_sanguin']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(int $id)
    {
        return Patient::with([
            'user',
            'dossierMedical.consultations.medecin.user',
            'dossierMedical.consultations.prescriptions.medicaments',
        ])->findOrFail($id);
    }

    public function findByMedecin(int $medecinId, array $filters = [], int $perPage = 15)
    {
        $patientIds = Consultation::where('medecin_id', $medecinId)
            ->join('dossiers_medicaux', 'consultations.dossier_medical_id', '=', 'dossiers_medicaux.id')
            ->pluck('dossiers_medicaux.patient_id')
            ->unique();

        $query = Patient::with('user')->whereIn('id', $patientIds);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', fn ($q) =>
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
            )->orWhere('num_dossier', 'like', "%{$search}%");
        }

        if (!empty($filters['sexe'])) {
            $query->where('sexe', $filters['sexe']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data)
    {
        return Patient::create($data);
    }

    public function update(int $id, array $data)
    {
        $patient = Patient::findOrFail($id);
        $patient->update($data);
        return $patient;
    }

    public function delete(int $id)
    {
        return Patient::findOrFail($id)->delete();
    }
}
