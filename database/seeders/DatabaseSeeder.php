<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Ordre strict respectant les clés étrangères :
     *
     * 1. Structures indépendantes  : centres_sante, laboratoires, pharmacies, medicaments
     * 2. Utilisateurs              : users → administrateurs, medecins, patients, pharmaciens, laborantins
     * 3. Dossiers médicaux         : dossiers_medicaux, carnets_vaccination
     * 4. Activité médicale         : rendez_vous, consultations, teleconsultations
     * 5. Prescriptions & analyses  : prescriptions (+pivot), demandes_analyses, resultats_analyses
     * 6. Compléments               : vaccins, campagnes, notifications, messages, tableaux_bord
     */
    public function run(): void
    {
        // ── Vider toutes les tables (TRUNCATE CASCADE compatible Neon.tech) ─
        // CASCADE propage la suppression aux tables dépendantes via les FK
        DB::statement('
            TRUNCATE TABLE
                tableaux_bord, messages, notifications, campagnes, vaccins,
                resultats_analyses, demandes_analyses, prescription_medicament,
                prescriptions, teleconsultations, consultations, rendez_vous,
                carnets_vaccination, dossiers_medicaux,
                laborantins, pharmaciens, administrateurs, patients, medecins,
                medicaments, pharmacies, laboratoires, centres_sante,
                oauth_access_tokens, oauth_refresh_tokens, oauth_auth_codes,
                oauth_clients, oauth_personal_access_clients,
                personal_access_tokens, users
            RESTART IDENTITY CASCADE
        ');

        $this->call([
            // ── Niveau 1 : Structures de base (sans FK) ──────────────────
            CentreSanteSeeder::class,
            LaboratoireSeeder::class,
            PharmacieSeeder::class,
            MedicamentSeeder::class,

            // ── Niveau 2 : Utilisateurs et profils ───────────────────────
            UserSeeder::class,
            ProfilSeeder::class,   // administrateurs, medecins, patients, pharmaciens, laborantins

            // ── Niveau 3 : Dossiers médicaux ─────────────────────────────
            DossierMedicalSeeder::class,
            CarnetVaccinationSeeder::class,

            // ── Niveau 4 : Activité médicale ─────────────────────────────
            RendezVousSeeder::class,
            ConsultationSeeder::class,
            TeleconsultationSeeder::class,

            // ── Niveau 5 : Prescriptions et analyses ─────────────────────
            PrescriptionSeeder::class,      // insère aussi prescription_medicament
            DemandeAnalyseSeeder::class,
            ResultatAnalyseSeeder::class,

            // ── Niveau 6 : Données complémentaires ───────────────────────
            VaccinSeeder::class,
            CampagneSeeder::class,
            NotificationSeeder::class,
            MessageSeeder::class,
            TableauBordSeeder::class,
        ]);
    }
}
