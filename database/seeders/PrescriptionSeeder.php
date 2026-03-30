<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $medecinId      = fn(string $mat) => DB::table('medecins')->where('matricule', $mat)->value('id');
        $pharmacieId    = fn(string $nom) => DB::table('pharmacies')->where('nom', $nom)->value('id');
        $consultationId = fn(string $motif) => DB::table('consultations')->where('motif', 'like', "%$motif%")->value('id');
        $medicamentId   = fn(string $nom, string $dosage) => DB::table('medicaments')
            ->where('nom', $nom)->where('dosage', $dosage)->value('id');

        // Prescription 1 : HTA patient 1
        $consult1 = $consultationId('Contrôle hypertension');
        $presc1Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult1,
            'medecin_id'      => $medecinId('MED-2024-001'),
            'numero'          => 'ORD-2024-001',
            'date_emission'   => '2024-10-05',
            'date_expiration' => '2025-04-05',
            'statut'          => 'delivree',
            'notes'           => 'Traitement de fond HTA. Renouvellement mensuel.',
            'pharmacie_id'    => $pharmacieId('Pharmacie du Plateau'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc1Id,
                'medicament_id'   => $medicamentId('Amlodipine', '5mg'),
                'posologie'       => '1 comprimé le matin',
                'duree_traitement'=> 30,
                'quantite'        => 30,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // Prescription 2 : HTA patient 1 (téléconsultation renouvellement)
        $consult2 = $consultationId('Renouvellement ordonnance - HTA');
        $presc2Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult2,
            'medecin_id'      => $medecinId('MED-2024-001'),
            'numero'          => 'ORD-2025-001',
            'date_emission'   => '2025-03-01',
            'date_expiration' => '2025-09-01',
            'statut'          => 'active',
            'notes'           => 'Renouvellement 6 mois.',
            'pharmacie_id'    => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc2Id,
                'medicament_id'   => $medicamentId('Amlodipine', '5mg'),
                'posologie'       => '1 comprimé le matin',
                'duree_traitement'=> 180,
                'quantite'        => 180,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc2Id,
                'medicament_id'   => $medicamentId('Acide Acétylsalicylique (Aspirine)', '100mg'),
                'posologie'       => '1 comprimé le soir après repas',
                'duree_traitement'=> 180,
                'quantite'        => 180,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // Prescription 3 : Diabète + HTA patient 3
        $consult3 = $consultationId('Bilan diabète');
        $presc3Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult3,
            'medecin_id'      => $medecinId('MED-2024-001'),
            'numero'          => 'ORD-2024-002',
            'date_emission'   => '2024-11-20',
            'date_expiration' => '2025-05-20',
            'statut'          => 'delivree',
            'notes'           => 'Adapter Metformine. Régime diabétique indispensable.',
            'pharmacie_id'    => $pharmacieId('Pharmacie de la Médina'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc3Id,
                'medicament_id'   => $medicamentId('Metformine', '500mg'),
                'posologie'       => '1 comprimé matin et soir pendant les repas',
                'duree_traitement'=> 30,
                'quantite'        => 60,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc3Id,
                'medicament_id'   => $medicamentId('Amlodipine', '5mg'),
                'posologie'       => '1 comprimé le matin',
                'duree_traitement'=> 30,
                'quantite'        => 30,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // Prescription 4 : Paludisme patient 4
        $consult4 = $consultationId('Fièvre, maux de tête');
        $presc4Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult4,
            'medecin_id'      => $medecinId('MED-2024-001'),
            'numero'          => 'ORD-2025-002',
            'date_emission'   => '2025-02-10',
            'date_expiration' => '2025-02-17',
            'statut'          => 'delivree',
            'notes'           => 'Traitement antipaludéen 3 jours. Bien respecter les prises avec nourriture.',
            'pharmacie_id'    => $pharmacieId('Pharmacie du Plateau'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc4Id,
                'medicament_id'   => $medicamentId('Arthémether + Luméfantrine (Coartem)', '20mg/120mg'),
                'posologie'       => '4 comprimés à H0, H8, H24, H36, H48, H60',
                'duree_traitement'=> 3,
                'quantite'        => 24,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc4Id,
                'medicament_id'   => $medicamentId('Paracétamol', '500mg'),
                'posologie'       => '2 comprimés toutes les 6 heures si fièvre',
                'duree_traitement'=> 3,
                'quantite'        => 24,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // Prescription 5 : Cardio patient 5
        $consult5 = $consultationId('Douleur thoracique');
        $presc5Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult5,
            'medecin_id'      => $medecinId('MED-2024-003'),
            'numero'          => 'ORD-2024-003',
            'date_emission'   => '2024-12-02',
            'date_expiration' => '2025-06-02',
            'statut'          => 'delivree',
            'notes'           => 'Ajout Métoprolol. Surveillance tension et FC.',
            'pharmacie_id'    => $pharmacieId('Pharmacie Keur Mbaye Fall'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc5Id,
                'medicament_id'   => $medicamentId('Acide Acétylsalicylique (Aspirine)', '100mg'),
                'posologie'       => '1 comprimé le soir après repas',
                'duree_traitement'=> 180,
                'quantite'        => 180,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc5Id,
                'medicament_id'   => $medicamentId('Ramipril', '5mg'),
                'posologie'       => '1 gélule le matin',
                'duree_traitement'=> 30,
                'quantite'        => 30,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc5Id,
                'medicament_id'   => $medicamentId('Metoprolol', '50mg'),
                'posologie'       => '1/2 comprimé matin et soir',
                'duree_traitement'=> 30,
                'quantite'        => 30,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // Prescription 6 : Asthme patient 2
        $consult6 = $consultationId("Crise d'asthme");
        $presc6Id = DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consult6,
            'medecin_id'      => $medecinId('MED-2024-002'),
            'numero'          => 'ORD-2024-004',
            'date_emission'   => '2024-10-12',
            'date_expiration' => '2025-04-12',
            'statut'          => 'delivree',
            'notes'           => 'Salbutamol en cas de crise. Béclométasone si non contrôlé.',
            'pharmacie_id'    => $pharmacieId('Pharmacie Dieye'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        DB::table('prescription_medicament')->insert([
            [
                'prescription_id' => $presc6Id,
                'medicament_id'   => $medicamentId('Salbutamol (Ventoline)', '2mg'),
                'posologie'       => '5ml (1 cuillère à café) 3x/jour',
                'duree_traitement'=> 7,
                'quantite'        => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'prescription_id' => $presc6Id,
                'medicament_id'   => $medicamentId('Prednisone', '20mg'),
                'posologie'       => '1 comprimé le matin pendant 5 jours',
                'duree_traitement'=> 5,
                'quantite'        => 5,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
