<?php

namespace App\Repositories\Eloquent;

use App\Models\Consultation;
use App\Repositories\Interfaces\ConsultationRepositoryInterface;

class ConsultationRepository implements ConsultationRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 20)
    {
        return $this->applyFilters(Consultation::query(), $filters)
            ->with(['dossierMedical.patient.user', 'medecin.user', 'prescriptions'])
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function find(int $id)
    {
        return Consultation::with([
            'dossierMedical.patient.user',
            'medecin.user',
            'prescriptions.medicaments',
            'teleconsultation',
        ])->findOrFail($id);
    }

    public function findByMedecin(int $medecinId, array $filters = [], int $perPage = 20)
    {
        $query = Consultation::where('medecin_id', $medecinId);
        return $this->applyFilters($query, $filters)
            ->with(['dossierMedical.patient.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function findByDossier(int $dossierId, array $filters = [], int $perPage = 10)
    {
        $query = Consultation::where('dossier_medical_id', $dossierId);

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date', '<=', $filters['date_fin']);
        }

        return $query->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Consultation::create($data);
    }

    public function update(int $id, array $data)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->update($data);
        return $consultation;
    }

    public function delete(int $id)
    {
        return Consultation::findOrFail($id)->delete();
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }
        if (!empty($filters['urgence'])) {
            $query->where('urgence', $filters['urgence']);
        }
        if (!empty($filters['type_consultation'])) {
            $query->where('type_consultation', $filters['type_consultation']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('dossierMedical.patient.user', fn ($q) =>
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
            )->orWhere('motif', 'like', "%{$search}%")
              ->orWhere('diagnostic', 'like', "%{$search}%");
        }
        return $query;
    }
}
