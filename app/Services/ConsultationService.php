<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\DossierMedical;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\RendezVous;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ConsultationService
{
    /**
     * Créer une nouvelle consultation.
     */
    public function creer(array $data, Medecin $medecin): Consultation
    {
        return DB::transaction(function () use ($data, $medecin) {
            // Retrouver ou créer le dossier médical du patient
            $dossier = DossierMedical::firstOrCreate(
                ['patient_id' => $data['patient_id']],
                [
                    'numero_dossier' => 'DOS-' . date('Y') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT),
                    'antecedents'    => null,
                    'allergies'      => null,
                ]
            );

            $consultation = Consultation::create([
                'dossier_medical_id'     => $dossier->id,
                'medecin_id'             => $medecin->id,
                'date'                   => $data['date'] ?? now(),
                'motif'                  => $data['motif'],
                'diagnostic'             => $data['diagnostic'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'tension'                => $data['tension'] ?? null,
                'poids'                  => $data['poids'] ?? null,
                'temperature'            => $data['temperature'] ?? null,
                'frequence_cardiaque'    => $data['frequence_cardiaque'] ?? null,
                'glycemie'               => $data['glycemie'] ?? null,
                'type_consultation'      => $data['type_consultation'] ?? 'presentiel',
                'taille'                 => $data['taille'] ?? null,
                'imc'                    => $this->calculerImc($data['poids'] ?? null, $data['taille'] ?? null),
                'saturation_oxygene'     => $data['saturation_oxygene'] ?? null,
                'frequence_respiratoire' => $data['frequence_respiratoire'] ?? null,
                'examen_clinique'        => $data['examen_clinique'] ?? null,
                'recommandations'        => $data['recommandations'] ?? null,
                'prochain_rdv'           => $data['prochain_rdv'] ?? null,
                'urgence'                => $data['urgence'] ?? false,
            ]);

            // Fermer le rendez-vous associé si fourni
            if (!empty($data['rendez_vous_id'])) {
                RendezVous::where('id', $data['rendez_vous_id'])
                    ->update(['statut' => 'termine']);
            }

            return $consultation->load('medecin.user', 'dossierMedical.patient.user');
        });
    }

    /**
     * Mettre à jour une consultation existante.
     */
    public function modifier(Consultation $consultation, array $data): Consultation
    {
        $fillable = [
            'diagnostic', 'notes', 'tension', 'poids', 'temperature',
            'frequence_cardiaque', 'glycemie', 'taille', 'saturation_oxygene',
            'frequence_respiratoire', 'examen_clinique', 'recommandations',
            'prochain_rdv', 'urgence',
        ];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $consultation->$field = $data[$field];
            }
        }

        if (isset($data['poids']) || isset($data['taille'])) {
            $consultation->imc = $this->calculerImc(
                $data['poids'] ?? $consultation->poids,
                $data['taille'] ?? $consultation->taille
            );
        }

        $consultation->save();

        return $consultation->fresh(['medecin.user', 'prescriptions.medicaments']);
    }

    /**
     * Récupérer l'historique des consultations d'un dossier.
     */
    public function historiqueDossier(DossierMedical $dossier, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $dossier->consultations()
            ->with(['medecin.user', 'prescriptions'])
            ->orderByDesc('date')
            ->paginate($perPage);
    }

    /**
     * Récupérer les consultations d'un médecin (today + recent).
     */
    public function consultationsMedecin(Medecin $medecin, ?string $date = null): Collection
    {
        $query = Consultation::where('medecin_id', $medecin->id)
            ->with(['dossierMedical.patient.user', 'prescriptions'])
            ->orderByDesc('date');

        if ($date) {
            $query->whereDate('date', $date);
        }

        return $query->limit(50)->get();
    }

    /**
     * Statistiques pour le tableau de bord médecin.
     */
    public function statistiquesMedecin(Medecin $medecin): array
    {
        $total     = Consultation::where('medecin_id', $medecin->id)->count();
        $aujourdhui = Consultation::where('medecin_id', $medecin->id)
            ->whereDate('date', today())->count();
        $semaine   = Consultation::where('medecin_id', $medecin->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $urgences  = Consultation::where('medecin_id', $medecin->id)
            ->where('urgence', true)->whereDate('date', today())->count();

        return compact('total', 'aujourdhui', 'semaine', 'urgences');
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function calculerImc(?float $poids, ?float $taille): ?float
    {
        if (!$poids || !$taille || $taille <= 0) {
            return null;
        }
        // taille en cm → mètres
        $tailleM = $taille > 3 ? $taille / 100 : $taille;
        return round($poids / ($tailleM ** 2), 1);
    }
}
