<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $userId = fn(string $email) => DB::table('users')->where('email', $email)->value('id');

        $notifications = [
            // Patient 1 : rappel RDV
            [
                'user_id'    => $userId('patient1@docsecur.sn'),
                'type'       => 'rappel_rdv',
                'message'    => 'Rappel : Vous avez un rendez-vous le 10/04/2026 à 09h00 avec Dr Sow Ibrahima pour un contrôle HTA.',
                'canal'      => 'sms',
                'date_envoi' => '2026-04-08 08:00:00',
                'est_lue'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Patient 1 : prescription prête
            [
                'user_id'    => $userId('patient1@docsecur.sn'),
                'type'       => 'prescription',
                'message'    => 'Votre ordonnance ORD-2025-001 a été transmise à votre pharmacie. Vous pouvez la récupérer.',
                'canal'      => 'push',
                'date_envoi' => '2025-03-01 17:00:00',
                'est_lue'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Patient 3 : résultats disponibles
            [
                'user_id'    => $userId('patient3@docsecur.sn'),
                'type'       => 'resultat_dispo',
                'message'    => 'Vos résultats d\'analyses (Glycémie + HbA1c) sont disponibles. Consultez votre dossier médical.',
                'canal'      => 'sms',
                'date_envoi' => '2024-11-23 14:00:00',
                'est_lue'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Patient 3 : suivi médicament
            [
                'user_id'    => $userId('patient3@docsecur.sn'),
                'type'       => 'suivi',
                'message'    => 'Rappel quotidien : Prenez votre Metformine 500mg avec le repas du matin et du soir.',
                'canal'      => 'sms',
                'date_envoi' => '2025-03-25 07:00:00',
                'est_lue'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Patient 4 : résultats paludisme
            [
                'user_id'    => $userId('patient4@docsecur.sn'),
                'type'       => 'resultat_dispo',
                'message'    => 'Vos résultats de test paludisme sont disponibles. Veuillez contacter votre médecin.',
                'canal'      => 'sms',
                'date_envoi' => '2025-02-10 13:30:00',
                'est_lue'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Patient 5 : rappel RDV cardio
            [
                'user_id'    => $userId('patient5@docsecur.sn'),
                'type'       => 'rappel_rdv',
                'message'    => 'Rappel : Rendez-vous cardiologie le 05/05/2026 à 08h30 avec Dr Diop Ousmane.',
                'canal'      => 'email',
                'date_envoi' => '2026-05-03 09:00:00',
                'est_lue'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Médecin 1 : notification nouveau patient
            [
                'user_id'    => $userId('medecin1@docsecur.sn'),
                'type'       => 'rappel_rdv',
                'message'    => 'Vous avez 2 rendez-vous demain. Patient: Ba Cheikh (09h00) et Faye Lamine (10h00).',
                'canal'      => 'push',
                'date_envoi' => '2026-04-09 18:00:00',
                'est_lue'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Campagne : notification tous utilisateurs (exemple patient 1 et 2)
            [
                'user_id'    => $userId('patient1@docsecur.sn'),
                'type'       => 'campagne',
                'message'    => 'Campagne de prévention des maladies cardiovasculaires : Mesurez votre tension gratuitement du 01/02 au 31/03/2026 dans votre centre de santé.',
                'canal'      => 'sms',
                'date_envoi' => '2026-02-01 09:00:00',
                'est_lue'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $userId('patient2@docsecur.sn'),
                'type'       => 'campagne',
                'message'    => 'Campagne de prévention des maladies cardiovasculaires : Mesurez votre tension gratuitement du 01/02 au 31/03/2026 dans votre centre de santé.',
                'canal'      => 'sms',
                'date_envoi' => '2026-02-01 09:00:00',
                'est_lue'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Pharmacien : médicament prêt
            [
                'user_id'    => $userId('patient1@docsecur.sn'),
                'type'       => 'medicament_pret',
                'message'    => 'Votre médicament Amlodipine est disponible à la Pharmacie du Plateau. Présentez votre ordonnance ORD-2024-001.',
                'canal'      => 'push',
                'date_envoi' => '2024-10-07 11:00:00',
                'est_lue'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('notifications')->insert($notifications);
    }
}
