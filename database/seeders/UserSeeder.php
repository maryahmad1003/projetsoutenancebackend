<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // ─── ADMINISTRATEURS ───────────────────────────────────────────
            [
                'nom'       => 'Diallo',
                'prenom'    => 'Moussa',
                'email'     => 'admin@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234500',
                'role'      => 'administrateur',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Ndiaye',
                'prenom'    => 'Aminata',
                'email'     => 'admin2@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234501',
                'role'      => 'administrateur',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ─── MÉDECINS ──────────────────────────────────────────────────
            [
                'nom'       => 'Sow',
                'prenom'    => 'Ibrahima',
                'email'     => 'medecin1@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234510',
                'role'      => 'medecin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Fall',
                'prenom'    => 'Fatou',
                'email'     => 'medecin2@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234511',
                'role'      => 'medecin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Diop',
                'prenom'    => 'Ousmane',
                'email'     => 'medecin3@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234512',
                'role'      => 'medecin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Sarr',
                'prenom'    => 'Mariama',
                'email'     => 'medecin4@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234513',
                'role'      => 'medecin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ─── PATIENTS ─────────────────────────────────────────────────
            [
                'nom'       => 'Ba',
                'prenom'    => 'Cheikh',
                'email'     => 'patient1@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234520',
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Mbaye',
                'prenom'    => 'Rokhaya',
                'email'     => 'patient2@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234521',
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Faye',
                'prenom'    => 'Lamine',
                'email'     => 'patient3@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234522',
                'role'      => 'patient',
                'langue'    => 'wo',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Gueye',
                'prenom'    => 'Aissatou',
                'email'     => 'patient4@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234523',
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Toure',
                'prenom'    => 'Modou',
                'email'     => 'patient5@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234524',
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Cissé',
                'prenom'    => 'Ndéye',
                'email'     => 'patient6@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234525',
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ─── PHARMACIENS ─────────────────────────────────────────────
            [
                'nom'       => 'Diouf',
                'prenom'    => 'Seydina',
                'email'     => 'pharmacien1@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234530',
                'role'      => 'pharmacien',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Kane',
                'prenom'    => 'Sophie',
                'email'     => 'pharmacien2@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234531',
                'role'      => 'pharmacien',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ─── LABORANTINS ─────────────────────────────────────────────
            [
                'nom'       => 'Sy',
                'prenom'    => 'Mamadou',
                'email'     => 'laborantin1@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234540',
                'role'      => 'laborantin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'       => 'Diallo',
                'prenom'    => 'Marème',
                'email'     => 'laborantin2@docsecur.sn',
                'password'  => Hash::make('password'),
                'telephone' => '+221771234541',
                'role'      => 'laborantin',
                'langue'    => 'fr',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insertOrIgnore($users);
    }
}
