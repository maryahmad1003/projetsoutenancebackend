<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RendezVousSeeder extends Seeder
{
    public function run(): void
    {
        $patientId  = fn(string $num) => DB::table('patients')->where('num_dossier', $num)->value('id');
        $medecinId  = fn(string $mat) => DB::table('medecins')->where('matricule', $mat)->value('id');

        $rendezVous = [
            // ── Passés ─────────────────────────────────────────────────────
            [
                'patient_id'    => $patientId('PAT-2024-001'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2024-10-05 09:00:00',
                'duree'         => 30,
                'motif'         => 'Contrôle hypertension',
                'statut'        => 'termine',
                'type'          => 'suivi',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-002'),
                'medecin_id'    => $medecinId('MED-2024-002'),
                'date_heure'    => '2024-10-12 10:30:00',
                'duree'         => 30,
                'motif'         => 'Consultation pédiatrique - enfant de 3 ans',
                'statut'        => 'termine',
                'type'          => 'consultation',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-003'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2024-11-20 08:00:00',
                'duree'         => 45,
                'motif'         => 'Bilan diabète et tension',
                'statut'        => 'termine',
                'type'          => 'suivi',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-005'),
                'medecin_id'    => $medecinId('MED-2024-003'),
                'date_heure'    => '2024-12-02 14:00:00',
                'duree'         => 60,
                'motif'         => 'Consultation cardiologie - douleur thoracique',
                'statut'        => 'termine',
                'type'          => 'consultation',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-006'),
                'medecin_id'    => $medecinId('MED-2024-004'),
                'date_heure'    => '2025-01-15 11:00:00',
                'duree'         => 40,
                'motif'         => 'Suivi post-césarienne et contraception',
                'statut'        => 'termine',
                'type'          => 'suivi',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-004'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2025-02-10 09:30:00',
                'duree'         => 30,
                'motif'         => 'Fièvre et maux de tête',
                'statut'        => 'termine',
                'type'          => 'consultation',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // ── Téléconsultations passées ──────────────────────────────────
            [
                'patient_id'    => $patientId('PAT-2024-001'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2025-03-01 16:00:00',
                'duree'         => 20,
                'motif'         => 'Renouvellement ordonnance HTA',
                'statut'        => 'termine',
                'type'          => 'teleconsultation',
                'rappel_envoye' => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // ── À venir / En attente ───────────────────────────────────────
            [
                'patient_id'    => $patientId('PAT-2024-001'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2026-04-10 09:00:00',
                'duree'         => 30,
                'motif'         => 'Contrôle tension et renouvellement ordonnance',
                'statut'        => 'confirme',
                'type'          => 'suivi',
                'rappel_envoye' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-003'),
                'medecin_id'    => $medecinId('MED-2024-001'),
                'date_heure'    => '2026-04-15 10:00:00',
                'duree'         => 45,
                'motif'         => 'Contrôle glycémie et bilan sanguin',
                'statut'        => 'en_attente',
                'type'          => 'suivi',
                'rappel_envoye' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-002'),
                'medecin_id'    => $medecinId('MED-2024-002'),
                'date_heure'    => '2026-04-20 14:30:00',
                'duree'         => 30,
                'motif'         => 'Vaccination enfant',
                'statut'        => 'confirme',
                'type'          => 'consultation',
                'rappel_envoye' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'patient_id'    => $patientId('PAT-2024-005'),
                'medecin_id'    => $medecinId('MED-2024-003'),
                'date_heure'    => '2026-05-05 08:30:00',
                'duree'         => 60,
                'motif'         => 'Bilan cardiaque annuel',
                'statut'        => 'confirme',
                'type'          => 'suivi',
                'rappel_envoye' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            // ── Annulé ────────────────────────────────────────────────────
            [
                'patient_id'    => $patientId('PAT-2024-004'),
                'medecin_id'    => $medecinId('MED-2024-002'),
                'date_heure'    => '2025-03-18 11:00:00',
                'duree'         => 30,
                'motif'         => 'Consultation de routine',
                'statut'        => 'annule',
                'type'          => 'consultation',
                'rappel_envoye' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        DB::table('rendez_vous')->insert($rendezVous);
    }
}
