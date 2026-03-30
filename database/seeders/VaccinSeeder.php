<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaccinSeeder extends Seeder
{
    public function run(): void
    {
        $carnetId  = fn(string $num) => DB::table('carnets_vaccination')
            ->join('patients', 'carnets_vaccination.patient_id', '=', 'patients.id')
            ->where('patients.num_dossier', $num)
            ->value('carnets_vaccination.id');

        $medecinId = fn(string $mat) => DB::table('medecins')->where('matricule', $mat)->value('id');

        $vaccins = [
            // Patient 1 (adulte)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-001'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Hépatite B', 'date_administration' => '2020-06-15', 'date_rappel' => null, 'lot' => 'HB-SN-2020-001'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-001'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Tétanos-Diphtérie (Td)', 'date_administration' => '2022-03-10', 'date_rappel' => '2032-03-10', 'lot' => 'TD-SN-2022-045'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-001'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'COVID-19 (AstraZeneca)', 'date_administration' => '2021-05-20', 'date_rappel' => null, 'lot' => 'COVID-AZ-2021-112'],

            // Patient 2 (femme en âge de procréer)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-002'), 'medecin_id' => $medecinId('MED-2024-002'), 'nom' => 'Rubéole-Rougeole-Oreillons (RRO)', 'date_administration' => '1994-07-22', 'date_rappel' => null, 'lot' => 'RRO-SN-94-007'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-002'), 'medecin_id' => $medecinId('MED-2024-002'), 'nom' => 'Hépatite B', 'date_administration' => '2015-04-12', 'date_rappel' => null, 'lot' => 'HB-SN-2015-032'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-002'), 'medecin_id' => $medecinId('MED-2024-002'), 'nom' => 'Tétanos-Diphtérie (Td)', 'date_administration' => '2023-09-05', 'date_rappel' => '2033-09-05', 'lot' => 'TD-SN-2023-078'],

            // Patient 3 (adulte avec comorbidités)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-003'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Grippe saisonnière', 'date_administration' => '2024-10-01', 'date_rappel' => '2025-10-01', 'lot' => 'FLU-SN-2024-210'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-003'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Pneumocoque (Pneumovax 23)', 'date_administration' => '2023-11-15', 'date_rappel' => null, 'lot' => 'PNV-SN-2023-055'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-003'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'COVID-19 (Pfizer BioNTech)', 'date_administration' => '2022-01-10', 'date_rappel' => null, 'lot' => 'COVID-PFZ-2022-041'],

            // Patient 4 (jeune adulte)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-004'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Méningite A (MenAfriVac)', 'date_administration' => '2012-12-01', 'date_rappel' => null, 'lot' => 'MEN-SN-2012-009'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-004'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'Fièvre Jaune', 'date_administration' => '2010-06-20', 'date_rappel' => null, 'lot' => 'FJ-SN-2010-017'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-004'), 'medecin_id' => $medecinId('MED-2024-001'), 'nom' => 'COVID-19 (Johnson & Johnson)', 'date_administration' => '2021-08-15', 'date_rappel' => null, 'lot' => 'COVID-JJ-2021-089'],

            // Patient 5 (senior cardiaque)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-005'), 'medecin_id' => $medecinId('MED-2024-003'), 'nom' => 'Grippe saisonnière', 'date_administration' => '2024-10-05', 'date_rappel' => '2025-10-05', 'lot' => 'FLU-SN-2024-215'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-005'), 'medecin_id' => $medecinId('MED-2024-003'), 'nom' => 'Pneumocoque (Pneumovax 23)', 'date_administration' => '2022-06-18', 'date_rappel' => null, 'lot' => 'PNV-SN-2022-030'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-005'), 'medecin_id' => $medecinId('MED-2024-003'), 'nom' => 'Tétanos-Diphtérie (Td)', 'date_administration' => '2019-04-10', 'date_rappel' => '2029-04-10', 'lot' => 'TD-SN-2019-064'],

            // Patient 6 (jeune femme)
            ['carnet_vaccination_id' => $carnetId('PAT-2024-006'), 'medecin_id' => $medecinId('MED-2024-004'), 'nom' => 'Tétanos-Diphtérie (Td)', 'date_administration' => '2023-02-20', 'date_rappel' => '2033-02-20', 'lot' => 'TD-SN-2023-041'],
            ['carnet_vaccination_id' => $carnetId('PAT-2024-006'), 'medecin_id' => $medecinId('MED-2024-004'), 'nom' => 'Hépatite B', 'date_administration' => '2022-08-10', 'date_rappel' => null, 'lot' => 'HB-SN-2022-088'],
        ];

        foreach ($vaccins as &$v) {
            $v['created_at'] = now();
            $v['updated_at'] = now();
        }

        DB::table('vaccins')->insert($vaccins);
    }
}
