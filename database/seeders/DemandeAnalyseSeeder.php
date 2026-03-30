<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemandeAnalyseSeeder extends Seeder
{
    public function run(): void
    {
        $medecinId     = fn(string $mat) => DB::table('medecins')->where('matricule', $mat)->value('id');
        $patientId     = fn(string $num) => DB::table('patients')->where('num_dossier', $num)->value('id');
        $laboratoireId = fn(string $nom) => DB::table('laboratoires')->where('nom', $nom)->value('id');

        $demandes = [
            // Demande 1 : Bilan glycémique patient 3 (diabète)
            [
                'medecin_id'     => $medecinId('MED-2024-001'),
                'patient_id'     => $patientId('PAT-2024-003'),
                'laboratoire_id' => $laboratoireId('Laboratoire National de Santé'),
                'type_analyse'   => 'Glycémie à jeun + HbA1c',
                'urgence'        => false,
                'notes'          => 'Patient diabétique. Contrôle trimestriel. Jeûne de 12h obligatoire.',
                'statut'         => 'terminee',
                'created_at'     => '2024-11-20 09:00:00',
                'updated_at'     => now(),
            ],
            // Demande 2 : Bilan lipidique patient 3
            [
                'medecin_id'     => $medecinId('MED-2024-001'),
                'patient_id'     => $patientId('PAT-2024-003'),
                'laboratoire_id' => $laboratoireId('Laboratoire National de Santé'),
                'type_analyse'   => 'Bilan lipidique complet (cholestérol total, LDL, HDL, triglycérides)',
                'urgence'        => false,
                'notes'          => 'Bilan lipidique dans le cadre du suivi diabète+HTA.',
                'statut'         => 'terminee',
                'created_at'     => '2024-11-20 09:05:00',
                'updated_at'     => now(),
            ],
            // Demande 3 : Test paludisme patient 4 (urgence)
            [
                'medecin_id'     => $medecinId('MED-2024-001'),
                'patient_id'     => $patientId('PAT-2024-004'),
                'laboratoire_id' => $laboratoireId('Laboratoire Biomedis Dakar'),
                'type_analyse'   => 'Test de Dépistage Rapide (TDR) Paludisme + Hémogramme',
                'urgence'        => true,
                'notes'          => 'Fièvre 39,2°C. Suspicion de paludisme. Résultat urgent.',
                'statut'         => 'terminee',
                'created_at'     => '2025-02-10 10:00:00',
                'updated_at'     => now(),
            ],
            // Demande 4 : Bilan cardiaque patient 5
            [
                'medecin_id'     => $medecinId('MED-2024-003'),
                'patient_id'     => $patientId('PAT-2024-005'),
                'laboratoire_id' => $laboratoireId('Laboratoire National de Santé'),
                'type_analyse'   => 'Bilan lipidique + Créatinine + Troponine',
                'urgence'        => true,
                'notes'          => 'Patient cardiaque. Douleur thoracique. Troponine en urgence.',
                'statut'         => 'terminee',
                'created_at'     => '2024-12-02 15:00:00',
                'updated_at'     => now(),
            ],
            // Demande 5 : NFS patient 1 (suivi HTA)
            [
                'medecin_id'     => $medecinId('MED-2024-001'),
                'patient_id'     => $patientId('PAT-2024-001'),
                'laboratoire_id' => $laboratoireId('Laboratoire Biomedis Dakar'),
                'type_analyse'   => 'NFS (Numération Formule Sanguine) + Ionogramme',
                'urgence'        => false,
                'notes'          => 'Bilan annuel. Vérifier tolérance traitement.',
                'statut'         => 'terminee',
                'created_at'     => '2024-10-05 10:00:00',
                'updated_at'     => now(),
            ],
            // Demande 6 : Sérologie hépatite patient 2 (en cours)
            [
                'medecin_id'     => $medecinId('MED-2024-002'),
                'patient_id'     => $patientId('PAT-2024-002'),
                'laboratoire_id' => $laboratoireId('Laboratoire Biomedis Dakar'),
                'type_analyse'   => 'Sérologie Hépatite B (Ag HBs, Ac anti-HBs) + Hépatite C',
                'urgence'        => false,
                'notes'          => 'Bilan pré-vaccinal hépatite B.',
                'statut'         => 'en_cours',
                'created_at'     => '2025-03-15 11:00:00',
                'updated_at'     => now(),
            ],
            // Demande 7 : Glycémie patient 3 (nouvelle demande)
            [
                'medecin_id'     => $medecinId('MED-2024-001'),
                'patient_id'     => $patientId('PAT-2024-003'),
                'laboratoire_id' => null,
                'type_analyse'   => 'Glycémie à jeun + Créatinine + Microalbuminurie',
                'urgence'        => false,
                'notes'          => 'Contrôle trimestriel diabète. Patient à choisir son laboratoire.',
                'statut'         => 'envoyee',
                'created_at'     => '2025-03-20 09:00:00',
                'updated_at'     => now(),
            ],
        ];

        DB::table('demandes_analyses')->insert($demandes);
    }
}
