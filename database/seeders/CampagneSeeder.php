<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampagneSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = fn(string $email) => DB::table('administrateurs')
            ->join('users', 'administrateurs.user_id', '=', 'users.id')
            ->where('users.email', $email)
            ->value('administrateurs.id');

        $campagnes = [
            [
                'administrateur_id' => $adminId('admin@docsecur.sn'),
                'titre'             => 'Campagne nationale de vaccination contre la méningite',
                'description'       => 'Vaccination gratuite contre la méningite A pour les enfants de 1 à 29 ans dans toutes les régions du Sénégal. Distribution de vaccins MenAfriVac dans les centres de santé.',
                'date_debut'        => '2025-01-15',
                'date_fin'          => '2025-02-28',
                'cible'             => 'Enfants et adultes de 1 à 29 ans',
                'region'            => 'Nationale',
                'type'              => 'vaccination',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'administrateur_id' => $adminId('admin@docsecur.sn'),
                'titre'             => 'Campagne de sensibilisation contre le paludisme',
                'description'       => 'Distribution de moustiquaires imprégnées d\'insecticide longue durée (MILD) et sensibilisation sur la prévention du paludisme en saison des pluies.',
                'date_debut'        => '2025-06-01',
                'date_fin'          => '2025-08-31',
                'cible'             => 'Femmes enceintes et enfants de moins de 5 ans',
                'region'            => 'Dakar',
                'type'              => 'prevention',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'administrateur_id' => $adminId('admin2@docsecur.sn'),
                'titre'             => 'Journée mondiale du diabète - Dépistage gratuit',
                'description'       => 'En marge de la Journée mondiale du diabète (14 novembre), organisation de consultations de dépistage gratuites avec glycémie capillaire dans les hôpitaux et centres de santé.',
                'date_debut'        => '2025-11-10',
                'date_fin'          => '2025-11-16',
                'cible'             => 'Adultes de plus de 30 ans',
                'region'            => 'Dakar, Thiès, Kaolack',
                'type'              => 'sensibilisation',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'administrateur_id' => $adminId('admin@docsecur.sn'),
                'titre'             => 'Campagne de vaccination contre la fièvre jaune',
                'description'       => 'Vaccination contre la fièvre jaune pour les populations des régions frontalières et les voyageurs. Carte jaune de vaccination délivrée gratuitement.',
                'date_debut'        => '2025-03-01',
                'date_fin'          => '2025-04-30',
                'cible'             => 'Toute la population',
                'region'            => 'Ziguinchor, Saint-Louis',
                'type'              => 'vaccination',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'administrateur_id' => $adminId('admin2@docsecur.sn'),
                'titre'             => 'Programme de prévention des maladies cardiovasculaires',
                'description'       => 'Sensibilisation sur l\'hypertension et les maladies cardiovasculaires. Mesure gratuite de la tension artérielle dans les espaces publics et marchés.',
                'date_debut'        => '2026-02-01',
                'date_fin'          => '2026-03-31',
                'cible'             => 'Adultes de plus de 40 ans',
                'region'            => 'Nationale',
                'type'              => 'prevention',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ];

        DB::table('campagnes')->insert($campagnes);
    }
}
