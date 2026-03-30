<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DossierMedicalSeeder extends Seeder
{
    public function run(): void
    {
        $patientId = fn(string $num) => DB::table('patients')
            ->where('num_dossier', $num)->value('id');

        $dossiers = [
            [
                'patient_id'     => $patientId('PAT-2024-001'),
                'numero_dossier' => 'DOS-2024-001',
                'antecedents'    => 'Hypertension artérielle (2018). Appendicectomie (2010). Allergie à la pénicilline.',
                'allergies'      => 'Pénicilline',
                'notes_generales'=> 'Patient suivi régulièrement pour HTA. Bonne observance thérapeutique.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'patient_id'     => $patientId('PAT-2024-002'),
                'numero_dossier' => 'DOS-2024-002',
                'antecedents'    => 'Asthme léger diagnostiqué en 2015. Mère diabétique.',
                'allergies'      => null,
                'notes_generales'=> 'Patiente asthmatique bien contrôlée sous bronchodilatateur à la demande.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'patient_id'     => $patientId('PAT-2024-003'),
                'numero_dossier' => 'DOS-2024-003',
                'antecedents'    => 'Diabète type 2 (2015). Hypertension artérielle. Allergie aux sulfamides.',
                'allergies'      => 'Sulfamides',
                'notes_generales'=> 'Patient obèse, suivi pour diabète et HTA. Nécessite contrôle glycémique mensuel.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'patient_id'     => $patientId('PAT-2024-004'),
                'numero_dossier' => 'DOS-2024-004',
                'antecedents'    => 'Aucun antécédent notable.',
                'allergies'      => null,
                'notes_generales'=> 'Patiente jeune et en bonne santé générale. Suivi préventif.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'patient_id'     => $patientId('PAT-2024-005'),
                'numero_dossier' => 'DOS-2024-005',
                'antecedents'    => 'Cardiopathie ischémique. Bypass coronarien (2019). Cholestérol. Allergie à l\'Aspirine forte dose.',
                'allergies'      => 'Aspirine forte dose',
                'notes_generales'=> 'Patient cardiaque sous surveillance rapprochée. Suivi cardio mensuel obligatoire.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'patient_id'     => $patientId('PAT-2024-006'),
                'numero_dossier' => 'DOS-2024-006',
                'antecedents'    => 'Césarienne (2022). Pas d\'allergie connue.',
                'allergies'      => null,
                'notes_generales'=> 'Patiente sans antécédents médicaux majeurs. Suivi gynécologique régulier.',
                'est_archive'    => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ];

        DB::table('dossiers_medicaux')->insert($dossiers);
    }
}
