<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaboratoireSeeder extends Seeder
{
    public function run(): void
    {
        $laboratoires = [
            [
                'nom'            => 'Laboratoire National de Santé',
                'adresse'        => 'Avenue Pasteur, Dakar',
                'telephone'      => '+221338393700',
                'types_analyses' => json_encode([
                    'Hémogramme', 'Glycémie', 'Cholestérol', 'Créatinine',
                    'Transaminases', 'Ionogramme', 'Hémoculture', 'Sérologie VIH',
                    'Test paludisme', 'Analyse urine', 'Bilan hépatique',
                ]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'nom'            => 'Laboratoire Biomedis Dakar',
                'adresse'        => 'Rue Carnot, Plateau, Dakar',
                'telephone'      => '+221338212345',
                'types_analyses' => json_encode([
                    'Hémogramme', 'Glycémie', 'NFS', 'PCR COVID',
                    'Sérologie hépatite B', 'Sérologie hépatite C',
                    'Bilan thyroïdien', 'PSA', 'Test grossesse', 'Coagulation',
                ]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'nom'            => 'Laboratoire Pasteur Thiès',
                'adresse'        => 'Avenue Léopold Sédar Senghor, Thiès',
                'telephone'      => '+221339514400',
                'types_analyses' => json_encode([
                    'Hémogramme', 'Glycémie', 'Cholestérol', 'Test paludisme',
                    'Analyse urine', 'Coproculture', 'Sérologie VIH',
                ]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'nom'            => 'Laboratoire Médical du Fleuve',
                'adresse'        => 'Rue Blanchot, Saint-Louis',
                'telephone'      => '+221961413322',
                'types_analyses' => json_encode([
                    'Hémogramme', 'Glycémie', 'Test paludisme',
                    'Analyse urine', 'Sérologie VIH', 'Ionogramme',
                ]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ];

        DB::table('laboratoires')->insert($laboratoires);
    }
}
