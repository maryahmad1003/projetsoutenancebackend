<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicamentSeeder extends Seeder
{
    public function run(): void
    {
        $medicaments = [
            // Antibiotiques
            ['nom' => 'Amoxicilline', 'dosage' => '500mg', 'forme' => 'gelule', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Amoxicilline + Acide clavulanique', 'dosage' => '875mg/125mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Azithromycine', 'dosage' => '250mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ciprofloxacine', 'dosage' => '500mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Métronidazole', 'dosage' => '500mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Doxycycline', 'dosage' => '100mg', 'forme' => 'gelule', 'created_at' => now(), 'updated_at' => now()],

            // Antipaludéens
            ['nom' => 'Arthémether + Luméfantrine (Coartem)', 'dosage' => '20mg/120mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Quinine', 'dosage' => '300mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Chloroquine', 'dosage' => '150mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],

            // Antalgiques & Anti-inflammatoires
            ['nom' => 'Paracétamol', 'dosage' => '500mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Paracétamol', 'dosage' => '250mg/5ml', 'forme' => 'sirop', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ibuprofène', 'dosage' => '400mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Diclofénac', 'dosage' => '75mg', 'forme' => 'injection', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Kétoprofène', 'dosage' => '2,5%', 'forme' => 'pommade', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Acide Acétylsalicylique (Aspirine)', 'dosage' => '100mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],

            // Antihypertenseurs & Cardiologie
            ['nom' => 'Amlodipine', 'dosage' => '5mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ramipril', 'dosage' => '5mg', 'forme' => 'gelule', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Metoprolol', 'dosage' => '50mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Furosémide', 'dosage' => '40mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Hydrochlorothiazide', 'dosage' => '25mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],

            // Diabète
            ['nom' => 'Metformine', 'dosage' => '500mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Glibenclamide', 'dosage' => '5mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Insuline Humaine (Actrapid)', 'dosage' => '100UI/ml', 'forme' => 'injection', 'created_at' => now(), 'updated_at' => now()],

            // Vitamines & Compléments
            ['nom' => 'Acide Folique', 'dosage' => '5mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Fer + Acide Folique', 'dosage' => '200mg/5mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Vitamine C', 'dosage' => '500mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Vitamine D3', 'dosage' => '1000UI', 'forme' => 'gelule', 'created_at' => now(), 'updated_at' => now()],

            // Respiratoire
            ['nom' => 'Salbutamol (Ventoline)', 'dosage' => '2mg', 'forme' => 'sirop', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Prednisone', 'dosage' => '20mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Béclamétasone', 'dosage' => '50mcg', 'forme' => 'autre', 'created_at' => now(), 'updated_at' => now()],

            // Digestif
            ['nom' => 'Oméprazole', 'dosage' => '20mg', 'forme' => 'gelule', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Métoclopramide', 'dosage' => '10mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Sels de Réhydratation Orale (SRO)', 'dosage' => '27,9g/sachet', 'forme' => 'autre', 'created_at' => now(), 'updated_at' => now()],

            // Dermatologie
            ['nom' => 'Bétaméthasone', 'dosage' => '0,1%', 'forme' => 'pommade', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Kétoconazole', 'dosage' => '2%', 'forme' => 'pommade', 'created_at' => now(), 'updated_at' => now()],

            // Antiparasitaires
            ['nom' => 'Albendazole', 'dosage' => '400mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ivermectine', 'dosage' => '3mg', 'forme' => 'comprime', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('medicaments')->insert($medicaments);
    }
}
