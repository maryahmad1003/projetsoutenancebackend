<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $userId = fn(string $email) => DB::table('users')->where('email', $email)->value('id');

        $med1    = $userId('medecin1@docsecur.sn');
        $med2    = $userId('medecin2@docsecur.sn');
        $med3    = $userId('medecin3@docsecur.sn');
        $pat1    = $userId('patient1@docsecur.sn');
        $pat2    = $userId('patient2@docsecur.sn');
        $pat3    = $userId('patient3@docsecur.sn');
        $pat5    = $userId('patient5@docsecur.sn');
        $pharm1  = $userId('pharmacien1@docsecur.sn');
        $lab1    = $userId('laborantin1@docsecur.sn');

        $messages = [
            // ── Conversation patient1 <-> medecin1 ────────────────────────
            ['expediteur_id' => $pat1, 'destinataire_id' => $med1, 'contenu' => 'Bonjour Docteur, j\'ai pris mes médicaments comme prescrit. Ma tension ce matin était 132/84. C\'est bien ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-03-02 08:30:00', 'created_at' => '2025-03-02 08:15:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $pat1, 'contenu' => 'Bonjour M. Ba, oui c\'est une bonne tension. Continuez le traitement et essayez de réduire le sel dans votre alimentation. À bientôt.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-03-02 10:00:00', 'created_at' => '2025-03-02 09:45:00', 'updated_at' => now()],
            ['expediteur_id' => $pat1, 'destinataire_id' => $med1, 'contenu' => 'Merci Docteur. J\'ai également noté quelques maux de tête ce week-end. Dois-je m\'inquiéter ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-03-03 09:00:00', 'created_at' => '2025-03-02 18:30:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $pat1, 'contenu' => 'Si les maux de tête persistent plus de 3 jours ou s\'accompagnent de vertiges, consultez en urgence. Sinon, ce peut être dû au stress ou à une déshydratation.', 'type' => 'texte', 'fichier_url' => null, 'lu' => false, 'lu_at' => null, 'created_at' => '2025-03-03 10:00:00', 'updated_at' => now()],

            // ── Conversation patient3 <-> medecin1 ────────────────────────
            ['expediteur_id' => $pat3, 'destinataire_id' => $med1, 'contenu' => 'Docteur, j\'ai du mal à contrôler mon alimentation. Avez-vous une liste de repas à éviter pour un diabétique ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-01-10 11:00:00', 'created_at' => '2025-01-10 09:00:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $pat3, 'contenu' => 'Bonjour M. Faye. Évitez : riz blanc en grande quantité, pain blanc, sucre, boissons sucrées, alcool. Privilégiez : légumes, poissons, viandes maigres, céréales complètes. Je vous envoie une fiche.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-01-10 14:00:00', 'created_at' => '2025-01-10 13:30:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $pat3, 'contenu' => 'Voici votre guide nutritionnel pour diabétiques.', 'type' => 'fichier', 'fichier_url' => 'https://storage.docsecur.sn/docs/guide-nutrition-diabete.pdf', 'lu' => true, 'lu_at' => '2025-01-10 15:00:00', 'created_at' => '2025-01-10 14:00:00', 'updated_at' => now()],

            // ── Conversation patient5 <-> medecin3 ────────────────────────
            ['expediteur_id' => $pat5, 'destinataire_id' => $med3, 'contenu' => 'Docteur Diop, j\'ai eu une légère douleur à la poitrine ce matin en montant les escaliers. C\'est inquiétant ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-02-15 09:00:00', 'created_at' => '2025-02-15 08:00:00', 'updated_at' => now()],
            ['expediteur_id' => $med3, 'destinataire_id' => $pat5, 'contenu' => 'M. Toure, c\'est important. Prenez votre Trinitrine si vous en avez. Si la douleur dure plus de 15 min ou s\'accompagne de sueurs, appelez le 15 ou allez directement aux urgences. Sinon venez me voir dès demain.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-02-15 09:30:00', 'created_at' => '2025-02-15 09:20:00', 'updated_at' => now()],
            ['expediteur_id' => $pat5, 'destinataire_id' => $med3, 'contenu' => 'Merci Docteur. La douleur a disparu après 5 minutes de repos. Je viens demain.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2025-02-15 10:00:00', 'created_at' => '2025-02-15 09:50:00', 'updated_at' => now()],

            // ── Conversation patient2 <-> medecin2 ────────────────────────
            ['expediteur_id' => $pat2, 'destinataire_id' => $med2, 'contenu' => 'Docteur, j\'ai eu une crise d\'asthme la nuit passée. J\'ai utilisé mon Ventoline. Dois-je venir vous voir ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => false, 'lu_at' => null, 'created_at' => '2025-03-20 07:30:00', 'updated_at' => now()],

            // ── Conversation pharmacien <-> médecin1 ─────────────────────
            ['expediteur_id' => $pharm1, 'destinataire_id' => $med1, 'contenu' => 'Bonjour Docteur Sow, l\'ordonnance ORD-2024-001 du patient Ba Cheikh est bien reçue. Le médicament a été délivré. Bonne journée.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2024-10-08 10:00:00', 'created_at' => '2024-10-08 09:30:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $pharm1, 'contenu' => 'Merci, bonne journée à vous également.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2024-10-08 11:00:00', 'created_at' => '2024-10-08 10:45:00', 'updated_at' => now()],

            // ── Conversation laborantin <-> médecin1 ─────────────────────
            ['expediteur_id' => $lab1, 'destinataire_id' => $med1, 'contenu' => 'Docteur Sow, les résultats d\'analyses du patient Faye Lamine (glycémie + HbA1c) sont disponibles. HbA1c à 9,8% - diabète très déséquilibré.', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2024-11-23 15:00:00', 'created_at' => '2024-11-23 14:30:00', 'updated_at' => now()],
            ['expediteur_id' => $med1, 'destinataire_id' => $lab1, 'contenu' => 'Merci pour l\'information. Je vais revoir le traitement du patient. Pouvez-vous lui envoyer un SMS pour qu\'il prenne rendez-vous ?', 'type' => 'texte', 'fichier_url' => null, 'lu' => true, 'lu_at' => '2024-11-23 16:00:00', 'created_at' => '2024-11-23 15:30:00', 'updated_at' => now()],
        ];

        DB::table('messages')->insert($messages);
    }
}
