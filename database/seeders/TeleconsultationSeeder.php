<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeleconsultationSeeder extends Seeder
{
    public function run(): void
    {
        $medecinId     = fn(string $mat) => DB::table('medecins')->where('matricule', $mat)->value('id');
        $patientId     = fn(string $num) => DB::table('patients')->where('num_dossier', $num)->value('id');
        $consultationId = fn(int $idx)    => DB::table('consultations')->orderBy('id')->skip($idx)->value('id');

        // La 2ème consultation (index 1) est la téléconsultation de renouvellement HTA
        $tcConsultationId = DB::table('consultations')
            ->where('type_consultation', 'teleconsultation')
            ->value('id');

        $teleconsultations = [
            [
                'consultation_id' => $tcConsultationId,
                'medecin_id'      => $medecinId('MED-2024-001'),
                'patient_id'      => $patientId('PAT-2024-001'),
                'date_debut'      => '2025-03-01 16:00:00',
                'date_fin'        => '2025-03-01 16:22:00',
                'lien_video'      => 'https://meet.docsecur.sn/room/tc-2025-0301-001',
                'statut'          => 'terminee',
                'enregistrement'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'consultation_id' => null,
                'medecin_id'      => $medecinId('MED-2024-003'),
                'patient_id'      => $patientId('PAT-2024-005'),
                'date_debut'      => '2025-04-10 15:00:00',
                'date_fin'        => '2025-04-10 15:35:00',
                'lien_video'      => 'https://meet.docsecur.sn/room/tc-2025-0410-002',
                'statut'          => 'terminee',
                'enregistrement'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // Planifiée
            [
                'consultation_id' => null,
                'medecin_id'      => $medecinId('MED-2024-001'),
                'patient_id'      => $patientId('PAT-2024-003'),
                'date_debut'      => '2026-04-15 14:00:00',
                'date_fin'        => null,
                'lien_video'      => 'https://meet.docsecur.sn/room/tc-2026-0415-003',
                'statut'          => 'planifiee',
                'enregistrement'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::table('teleconsultations')->insert($teleconsultations);
    }
}
