<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée les profils métiers (administrateurs, medecins, patients,
 * pharmaciens, laborantins) en se basant sur les IDs des users insérés.
 *
 * On récupère les IDs par email pour rester indépendant de l'auto-incrément.
 */
class ProfilSeeder extends Seeder
{
    public function run(): void
    {
        // ── Helpers ────────────────────────────────────────────────────────
        $userId = fn(string $email) => DB::table('users')
            ->where('email', $email)->value('id');

        $centreId = fn(string $nom) => DB::table('centres_sante')
            ->where('nom', $nom)->value('id');

        $pharmacieId = fn(string $nom) => DB::table('pharmacies')
            ->where('nom', $nom)->value('id');

        $laboratoireId = fn(string $nom) => DB::table('laboratoires')
            ->where('nom', $nom)->value('id');

        // ── ADMINISTRATEURS ────────────────────────────────────────────────
        DB::table('administrateurs')->insertOrIgnore([
            ['user_id' => $userId('admin@docsecur.sn'),  'niveau' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $userId('admin2@docsecur.sn'), 'niveau' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── MÉDECINS ───────────────────────────────────────────────────────
        DB::table('medecins')->insertOrIgnore([
            [
                'user_id'         => $userId('medecin1@docsecur.sn'),
                'centre_sante_id' => $centreId('Hôpital Principal de Dakar'),
                'matricule'       => 'MED-2024-001',
                'specialite'      => 'Médecine Générale',
                'num_ordre'       => 'OMS-SN-1021',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'user_id'         => $userId('medecin2@docsecur.sn'),
                'centre_sante_id' => $centreId('Hôpital Aristide Le Dantec'),
                'matricule'       => 'MED-2024-002',
                'specialite'      => 'Pédiatrie',
                'num_ordre'       => 'OMS-SN-1022',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'user_id'         => $userId('medecin3@docsecur.sn'),
                'centre_sante_id' => $centreId('Clinique de la Madeleine'),
                'matricule'       => 'MED-2024-003',
                'specialite'      => 'Cardiologie',
                'num_ordre'       => 'OMS-SN-1023',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'user_id'         => $userId('medecin4@docsecur.sn'),
                'centre_sante_id' => $centreId('Centre de Santé de Thiès'),
                'matricule'       => 'MED-2024-004',
                'specialite'      => 'Gynécologie-Obstétrique',
                'num_ordre'       => 'OMS-SN-1024',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // ── PATIENTS ───────────────────────────────────────────────────────
        $patients = [
            [
                'user_id'                  => $userId('patient1@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-001',
                'date_naissance'           => '1985-03-15',
                'sexe'                     => 'M',
                'adresse'                  => 'Rue 12, Médina, Dakar',
                'groupe_sanguin'           => 'O+',
                'personne_contact'         => 'Aminata Ba',
                'tel_contact'              => '+221771234600',
                'taille'                   => 175.0,
                'poids'                    => 78.0,
                'profession'               => 'Enseignant',
                'situation_matrimoniale'   => 'Marié',
                'nombre_enfants'           => 3,
                'antecedents_medicaux'     => 'Hypertension artérielle depuis 2018',
                'antecedents_chirurgicaux' => 'Appendicectomie en 2010',
                'antecedents_familiaux'    => 'Père diabétique',
                'allergies'                => 'Pénicilline',
                'traitement_en_cours'      => 'Amlodipine 5mg/jour',
                'assurance'                => 'IPRES',
                'numero_assurance'         => 'IPRES-2024-00001',
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
            [
                'user_id'                  => $userId('patient2@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-002',
                'date_naissance'           => '1992-07-22',
                'sexe'                     => 'F',
                'adresse'                  => 'HLM Grand Yoff, Dakar',
                'groupe_sanguin'           => 'A+',
                'personne_contact'         => 'Ibou Mbaye',
                'tel_contact'              => '+221771234601',
                'taille'                   => 162.0,
                'poids'                    => 58.0,
                'profession'               => 'Infirmière',
                'situation_matrimoniale'   => 'Mariée',
                'nombre_enfants'           => 2,
                'antecedents_medicaux'     => 'Asthme léger',
                'antecedents_chirurgicaux' => null,
                'antecedents_familiaux'    => 'Mère diabétique',
                'allergies'                => null,
                'traitement_en_cours'      => 'Salbutamol en cas de crise',
                'assurance'                => 'CSS',
                'numero_assurance'         => 'CSS-2024-00002',
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
            [
                'user_id'                  => $userId('patient3@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-003',
                'date_naissance'           => '1970-11-05',
                'sexe'                     => 'M',
                'adresse'                  => 'Parcelles Assainies, Dakar',
                'groupe_sanguin'           => 'B+',
                'personne_contact'         => 'Khady Faye',
                'tel_contact'              => '+221771234602',
                'taille'                   => 170.0,
                'poids'                    => 90.0,
                'profession'               => 'Commerçant',
                'situation_matrimoniale'   => 'Marié',
                'nombre_enfants'           => 5,
                'antecedents_medicaux'     => 'Diabète de type 2 depuis 2015, Hypertension',
                'antecedents_chirurgicaux' => null,
                'antecedents_familiaux'    => 'Père et mère hypertendus',
                'allergies'                => 'Sulfamides',
                'traitement_en_cours'      => 'Metformine 500mg 2x/jour, Amlodipine 5mg',
                'assurance'                => null,
                'numero_assurance'         => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
            [
                'user_id'                  => $userId('patient4@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-004',
                'date_naissance'           => '1998-01-30',
                'sexe'                     => 'F',
                'adresse'                  => 'Liberté 6, Dakar',
                'groupe_sanguin'           => 'AB+',
                'personne_contact'         => 'Mamadou Gueye',
                'tel_contact'              => '+221771234603',
                'taille'                   => 168.0,
                'poids'                    => 65.0,
                'profession'               => 'Étudiante',
                'situation_matrimoniale'   => 'Célibataire',
                'nombre_enfants'           => 0,
                'antecedents_medicaux'     => null,
                'antecedents_chirurgicaux' => null,
                'antecedents_familiaux'    => null,
                'allergies'                => null,
                'traitement_en_cours'      => null,
                'assurance'                => 'Mutuelle',
                'numero_assurance'         => 'MUT-2024-00004',
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
            [
                'user_id'                  => $userId('patient5@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-005',
                'date_naissance'           => '1960-08-14',
                'sexe'                     => 'M',
                'adresse'                  => 'Thiès Centre',
                'groupe_sanguin'           => 'O-',
                'personne_contact'         => 'Fatou Toure',
                'tel_contact'              => '+221771234604',
                'taille'                   => 168.0,
                'poids'                    => 72.0,
                'profession'               => 'Retraité',
                'situation_matrimoniale'   => 'Marié',
                'nombre_enfants'           => 6,
                'antecedents_medicaux'     => 'Cardiopathie ischémique, Cholestérol',
                'antecedents_chirurgicaux' => 'Bypass coronarien en 2019',
                'antecedents_familiaux'    => 'Père décédé d\'une crise cardiaque',
                'allergies'                => 'Aspirine forte dose',
                'traitement_en_cours'      => 'Aspirine 100mg, Atorvastatine 40mg, Ramipril 5mg',
                'assurance'                => 'IPRES',
                'numero_assurance'         => 'IPRES-2024-00005',
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
            [
                'user_id'                  => $userId('patient6@docsecur.sn'),
                'num_dossier'              => 'PAT-2024-006',
                'date_naissance'           => '1995-05-18',
                'sexe'                     => 'F',
                'adresse'                  => 'Sacré-Cœur 3, Dakar',
                'groupe_sanguin'           => 'A-',
                'personne_contact'         => 'Omar Cissé',
                'tel_contact'              => '+221771234605',
                'taille'                   => 165.0,
                'poids'                    => 62.0,
                'profession'               => 'Secrétaire',
                'situation_matrimoniale'   => 'Mariée',
                'nombre_enfants'           => 1,
                'antecedents_medicaux'     => null,
                'antecedents_chirurgicaux' => 'Césarienne en 2022',
                'antecedents_familiaux'    => null,
                'allergies'                => null,
                'traitement_en_cours'      => null,
                'assurance'                => 'CSS',
                'numero_assurance'         => 'CSS-2024-00006',
                'created_at'               => now(),
                'updated_at'               => now(),
            ],
        ];

        DB::table('patients')->insertOrIgnore($patients);

        // ── PHARMACIENS ────────────────────────────────────────────────────
        DB::table('pharmaciens')->insertOrIgnore([
            [
                'user_id'      => $userId('pharmacien1@docsecur.sn'),
                'pharmacie_id' => $pharmacieId('Pharmacie du Plateau'),
                'num_licence'  => 'LIC-PH-2024-001',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'user_id'      => $userId('pharmacien2@docsecur.sn'),
                'pharmacie_id' => $pharmacieId('Pharmacie Dieye'),
                'num_licence'  => 'LIC-PH-2024-002',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // ── LABORANTINS ────────────────────────────────────────────────────
        DB::table('laborantins')->insertOrIgnore([
            [
                'user_id'         => $userId('laborantin1@docsecur.sn'),
                'laboratoire_id'  => $laboratoireId('Laboratoire National de Santé'),
                'num_agrement'    => 'AGR-LAB-2024-001',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'user_id'         => $userId('laborantin2@docsecur.sn'),
                'laboratoire_id'  => $laboratoireId('Laboratoire Biomedis Dakar'),
                'num_agrement'    => 'AGR-LAB-2024-002',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
