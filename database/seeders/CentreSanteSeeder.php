<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentreSanteSeeder extends Seeder
{
    public function run(): void
    {
        $centres = [
            [
                'nom'             => 'Hôpital Principal de Dakar',
                'adresse'         => 'Avenue Nelson Mandela, Dakar',
                'telephone'       => '+221338392000',
                'type'            => 'hopital',
                'region'          => 'Dakar',
                'coordonnees_gps' => '14.6928,-17.4467',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Hôpital Aristide Le Dantec',
                'adresse'         => 'Avenue Pasteur, Dakar',
                'telephone'       => '+221338237491',
                'type'            => 'hopital',
                'region'          => 'Dakar',
                'coordonnees_gps' => '14.6752,-17.4396',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Clinique de la Madeleine',
                'adresse'         => 'Rue de la Madeleine, Dakar',
                'telephone'       => '+221338220720',
                'type'            => 'clinique',
                'region'          => 'Dakar',
                'coordonnees_gps' => '14.6810,-17.4440',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Centre de Santé de Thiès',
                'adresse'         => 'Avenue Léopold Sédar Senghor, Thiès',
                'telephone'       => '+221339512030',
                'type'            => 'centre_sante',
                'region'          => 'Thiès',
                'coordonnees_gps' => '14.7886,-16.9260',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Hôpital Régional de Saint-Louis',
                'adresse'         => 'Boulevard Général de Gaulle, Saint-Louis',
                'telephone'       => '+221961410050',
                'type'            => 'hopital',
                'region'          => 'Saint-Louis',
                'coordonnees_gps' => '16.0179,-16.4896',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Poste de Santé de Yoff',
                'adresse'         => 'Village de Yoff, Dakar',
                'telephone'       => '+221338204510',
                'type'            => 'poste_sante',
                'region'          => 'Dakar',
                'coordonnees_gps' => '14.7430,-17.4900',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Clinique Ngom Santé',
                'adresse'         => 'Rue 10, Ziguinchor',
                'telephone'       => '+221993412275',
                'type'            => 'clinique',
                'region'          => 'Ziguinchor',
                'coordonnees_gps' => '12.5583,-16.2719',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nom'             => 'Centre de Santé de Kaolack',
                'adresse'         => 'Avenue du Général de Gaulle, Kaolack',
                'telephone'       => '+221941523060',
                'type'            => 'centre_sante',
                'region'          => 'Kaolack',
                'coordonnees_gps' => '14.1486,-16.0725',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::table('centres_sante')->insert($centres);
    }
}
