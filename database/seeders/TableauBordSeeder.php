<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableauBordSeeder extends Seeder
{
    public function run(): void
    {
        $centreId = fn(string $nom) => DB::table('centres_sante')->where('nom', $nom)->value('id');

        $tableaux = [
            [
                'centre_sante_id'          => $centreId('Hôpital Principal de Dakar'),
                'date_generation'           => '2025-01-31 23:59:00',
                'nombre_patients'           => 1245,
                'nombre_consultations'      => 3820,
                'pathologies_frequentes'    => json_encode([
                    ['pathologie' => 'Paludisme',             'nombre' => 820, 'pourcentage' => 21.5],
                    ['pathologie' => 'Hypertension artérielle','nombre' => 645, 'pourcentage' => 16.9],
                    ['pathologie' => 'Diabète type 2',         'nombre' => 510, 'pourcentage' => 13.4],
                    ['pathologie' => 'Infections respiratoires','nombre' => 480, 'pourcentage' => 12.6],
                    ['pathologie' => 'Gastro-entérites',       'nombre' => 390, 'pourcentage' => 10.2],
                ]),
                'indicateurs_performance'   => json_encode([
                    'taux_occupation_lits'           => 78.5,
                    'duree_sejour_moyenne_jours'      => 4.2,
                    'taux_consultation_urgence'       => 22.3,
                    'taux_satisfaction_patients'      => 82.0,
                    'delai_attente_moyen_minutes'     => 45,
                ]),
                'created_at'                => now(),
                'updated_at'                => now(),
            ],
            [
                'centre_sante_id'          => $centreId('Hôpital Aristide Le Dantec'),
                'date_generation'           => '2025-01-31 23:59:00',
                'nombre_patients'           => 980,
                'nombre_consultations'      => 2640,
                'pathologies_frequentes'    => json_encode([
                    ['pathologie' => 'Maladies cardiovasculaires', 'nombre' => 520, 'pourcentage' => 19.7],
                    ['pathologie' => 'Asthme et allergies',        'nombre' => 390, 'pourcentage' => 14.8],
                    ['pathologie' => 'Paludisme',                  'nombre' => 360, 'pourcentage' => 13.6],
                    ['pathologie' => 'Maladies rénales',           'nombre' => 290, 'pourcentage' => 11.0],
                    ['pathologie' => 'Cancer',                     'nombre' => 200, 'pourcentage' => 7.6],
                ]),
                'indicateurs_performance'   => json_encode([
                    'taux_occupation_lits'           => 85.2,
                    'duree_sejour_moyenne_jours'      => 5.8,
                    'taux_consultation_urgence'       => 28.5,
                    'taux_satisfaction_patients'      => 79.5,
                    'delai_attente_moyen_minutes'     => 60,
                ]),
                'created_at'                => now(),
                'updated_at'                => now(),
            ],
            [
                'centre_sante_id'          => $centreId('Centre de Santé de Thiès'),
                'date_generation'           => '2025-01-31 23:59:00',
                'nombre_patients'           => 620,
                'nombre_consultations'      => 1850,
                'pathologies_frequentes'    => json_encode([
                    ['pathologie' => 'Paludisme',              'nombre' => 580, 'pourcentage' => 31.4],
                    ['pathologie' => 'Diarrhées',              'nombre' => 310, 'pourcentage' => 16.8],
                    ['pathologie' => 'Infections urinaires',   'nombre' => 245, 'pourcentage' => 13.2],
                    ['pathologie' => 'Hypertension',           'nombre' => 190, 'pourcentage' => 10.3],
                    ['pathologie' => 'Malnutrition infantile', 'nombre' => 155, 'pourcentage' => 8.4],
                ]),
                'indicateurs_performance'   => json_encode([
                    'taux_occupation_lits'           => 65.0,
                    'duree_sejour_moyenne_jours'      => 3.1,
                    'taux_consultation_urgence'       => 15.2,
                    'taux_satisfaction_patients'      => 86.0,
                    'delai_attente_moyen_minutes'     => 30,
                ]),
                'created_at'                => now(),
                'updated_at'                => now(),
            ],
            [
                'centre_sante_id'          => $centreId('Hôpital Principal de Dakar'),
                'date_generation'           => '2026-01-31 23:59:00',
                'nombre_patients'           => 1380,
                'nombre_consultations'      => 4120,
                'pathologies_frequentes'    => json_encode([
                    ['pathologie' => 'Paludisme',              'nombre' => 870, 'pourcentage' => 21.1],
                    ['pathologie' => 'Hypertension',           'nombre' => 720, 'pourcentage' => 17.5],
                    ['pathologie' => 'Diabète type 2',         'nombre' => 590, 'pourcentage' => 14.3],
                    ['pathologie' => 'Infections respiratoires','nombre' => 510, 'pourcentage' => 12.4],
                    ['pathologie' => 'Grossesses pathologiques','nombre' => 340, 'pourcentage' => 8.3],
                ]),
                'indicateurs_performance'   => json_encode([
                    'taux_occupation_lits'           => 80.1,
                    'duree_sejour_moyenne_jours'      => 4.0,
                    'taux_consultation_urgence'       => 23.8,
                    'taux_satisfaction_patients'      => 84.0,
                    'delai_attente_moyen_minutes'     => 40,
                ]),
                'created_at'                => now(),
                'updated_at'                => now(),
            ],
        ];

        DB::table('tableaux_bord')->insert($tableaux);
    }
}
