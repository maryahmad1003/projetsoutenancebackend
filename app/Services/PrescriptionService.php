<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Medecin;
use App\Models\Prescription;
use App\Models\Medicament;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class PrescriptionService
{
    /**
     * Créer une ordonnance avec ses médicaments.
     *
     * $data = [
     *   'consultation_id'  => int,
     *   'date_expiration'  => 'YYYY-MM-DD' (optionnel, défaut +3 mois),
     *   'notes'            => string,
     *   'pharmacie_id'     => int|null,
     *   'medicaments' => [
     *     ['medicament_id' => int, 'posologie' => string, 'duree_traitement' => string, 'quantite' => int],
     *     ...
     *   ],
     * ]
     */
    public function creer(array $data, Medecin $medecin): Prescription
    {
        return DB::transaction(function () use ($data, $medecin) {
            $prescription = Prescription::create([
                'consultation_id' => $data['consultation_id'],
                'medecin_id'      => $medecin->id,
                'numero'          => $this->generateNumero(),
                'date_emission'   => now()->toDateString(),
                'date_expiration' => $data['date_expiration'] ?? now()->addMonths(3)->toDateString(),
                'statut'          => 'active',
                'notes'           => $data['notes'] ?? null,
                'pharmacie_id'    => $data['pharmacie_id'] ?? null,
            ]);

            // Attacher les médicaments avec posologie
            if (!empty($data['medicaments'])) {
                foreach ($data['medicaments'] as $med) {
                    $prescription->medicaments()->attach($med['medicament_id'], [
                        'posologie'        => $med['posologie'] ?? null,
                        'duree_traitement' => $med['duree_traitement'] ?? null,
                        'quantite'         => $med['quantite'] ?? 1,
                    ]);
                }
            }

            return $prescription->load('medicaments', 'medecin.user');
        });
    }

    /**
     * Marquer une prescription comme dispensée / terminée.
     */
    public function marquerDispensee(Prescription $prescription, ?int $pharmacieId = null): Prescription
    {
        $prescription->update([
            'statut'      => 'dispensee',
            'pharmacie_id' => $pharmacieId ?? $prescription->pharmacie_id,
        ]);

        return $prescription->fresh('medicaments');
    }

    /**
     * Récupérer les prescriptions d'un patient (via ses consultations).
     */
    public function prescriptionsPatient(int $patientId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Prescription::whereHas('consultation.dossierMedical', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->with(['medicaments', 'medecin.user', 'consultation'])
            ->orderByDesc('date_emission')
            ->paginate($perPage);
    }

    /**
     * Récupérer les prescriptions émises par un médecin.
     */
    public function prescriptionsMedecin(Medecin $medecin, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Prescription::where('medecin_id', $medecin->id)
            ->with(['medicaments', 'consultation.dossierMedical.patient.user'])
            ->orderByDesc('date_emission')
            ->paginate($perPage);
    }

    /**
     * Récupérer les ordonnances en attente pour un pharmacien.
     */
    public function ordonnancesEnAttente(): Collection
    {
        return Prescription::where('statut', 'active')
            ->where('date_expiration', '>=', today())
            ->with(['medicaments', 'medecin.user', 'consultation.dossierMedical.patient.user'])
            ->orderByDesc('date_emission')
            ->get();
    }

    /**
     * Vérifier si une prescription est encore valide.
     */
    public function estValide(Prescription $prescription): bool
    {
        return $prescription->statut === 'active'
            && $prescription->date_expiration >= today()->toDateString();
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function generateNumero(): string
    {
        return 'ORD-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
