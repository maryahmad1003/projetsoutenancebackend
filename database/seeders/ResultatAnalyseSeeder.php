<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResultatAnalyseSeeder extends Seeder
{
    public function run(): void
    {
        $laborantinId  = fn(string $agr) => DB::table('laborantins')->where('num_agrement', $agr)->value('id');
        $dossierId     = fn(string $num) => DB::table('dossiers_medicaux')->where('numero_dossier', $num)->value('id');

        // Récupère les IDs des demandes terminées par type
        $demandeId = fn(string $type) => DB::table('demandes_analyses')
            ->where('type_analyse', 'like', "%$type%")->where('statut', 'terminee')->value('id');

        $resultats = [
            // Résultat 1 : Glycémie + HbA1c patient 3 (diabète)
            [
                'demande_analyse_id' => $demandeId('Glycémie à jeun + HbA1c'),
                'dossier_medical_id' => $dossierId('DOS-2024-003'),
                'laborantin_id'      => $laborantinId('AGR-LAB-2024-001'),
                'type_analyse'       => 'Glycémie à jeun + HbA1c',
                'date_prelevement'   => '2024-11-22',
                'date_resultat'      => '2024-11-23',
                'resultats'          => json_encode([
                    'Glycémie à jeun' => '2,1 g/L',
                    'HbA1c'           => '9,8%',
                ]),
                'valeur_normale'     => json_encode([
                    'Glycémie à jeun' => '0,7 - 1,1 g/L',
                    'HbA1c'           => '< 7%',
                ]),
                'interpretation'     => 'Diabète déséquilibré. Glycémie et HbA1c très élevées. Adaptation thérapeutique urgente.',
                'fichier_joint'      => null,
                'statut'             => 'consulte',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // Résultat 2 : Bilan lipidique patient 3
            [
                'demande_analyse_id' => $demandeId('Bilan lipidique complet'),
                'dossier_medical_id' => $dossierId('DOS-2024-003'),
                'laborantin_id'      => $laborantinId('AGR-LAB-2024-001'),
                'type_analyse'       => 'Bilan lipidique',
                'date_prelevement'   => '2024-11-22',
                'date_resultat'      => '2024-11-23',
                'resultats'          => json_encode([
                    'Cholestérol total' => '2,45 g/L',
                    'LDL'               => '1,65 g/L',
                    'HDL'               => '0,42 g/L',
                    'Triglycérides'     => '2,10 g/L',
                ]),
                'valeur_normale'     => json_encode([
                    'Cholestérol total' => '< 2,0 g/L',
                    'LDL'               => '< 1,0 g/L',
                    'HDL'               => '> 0,6 g/L',
                    'Triglycérides'     => '< 1,5 g/L',
                ]),
                'interpretation'     => 'Dyslipidémie mixte. Cholestérol, LDL et triglycérides élevés. HDL bas. Risque cardiovasculaire élevé.',
                'fichier_joint'      => null,
                'statut'             => 'consulte',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // Résultat 3 : TDR Paludisme patient 4
            [
                'demande_analyse_id' => $demandeId('TDR'),
                'dossier_medical_id' => $dossierId('DOS-2024-004'),
                'laborantin_id'      => $laborantinId('AGR-LAB-2024-002'),
                'type_analyse'       => 'TDR Paludisme + Hémogramme',
                'date_prelevement'   => '2025-02-10',
                'date_resultat'      => '2025-02-10',
                'resultats'          => json_encode([
                    'TDR Plasmodium falciparum' => 'POSITIF',
                    'Hémoglobine'               => '11,5 g/dL',
                    'Globules blancs'           => '12 500/mm³',
                    'Plaquettes'                => '98 000/mm³',
                ]),
                'valeur_normale'     => json_encode([
                    'TDR'            => 'Négatif',
                    'Hémoglobine'    => '12-16 g/dL',
                    'Globules blancs'=> '4000-10000/mm³',
                    'Plaquettes'     => '150 000-400 000/mm³',
                ]),
                'interpretation'     => 'Paludisme à Plasmodium falciparum confirmé. Anémie modérée. Thrombopénie légère. Traitement antipaludéen immédiat.',
                'fichier_joint'      => null,
                'statut'             => 'consulte',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // Résultat 4 : Bilan cardiaque patient 5
            [
                'demande_analyse_id' => $demandeId('Troponine'),
                'dossier_medical_id' => $dossierId('DOS-2024-005'),
                'laborantin_id'      => $laborantinId('AGR-LAB-2024-001'),
                'type_analyse'       => 'Bilan lipidique + Créatinine + Troponine',
                'date_prelevement'   => '2024-12-02',
                'date_resultat'      => '2024-12-02',
                'resultats'          => json_encode([
                    'Cholestérol LDL' => '1,85 g/L',
                    'Cholestérol HDL' => '0,38 g/L',
                    'Triglycérides'   => '1,90 g/L',
                    'Créatinine'      => '105 µmol/L',
                    'Troponine I'     => '0,02 ng/mL',
                ]),
                'valeur_normale'     => json_encode([
                    'LDL'         => '< 0,7 g/L (patient cardiaque)',
                    'HDL'         => '> 0,6 g/L',
                    'Créatinine'  => '60-110 µmol/L',
                    'Troponine I' => '< 0,04 ng/mL',
                ]),
                'interpretation'     => 'LDL très élevé chez patient coronarien (objectif < 0,7). Pas d\'élévation de troponine (pas d\'IDM aigu). Insuffisance rénale légère.',
                'fichier_joint'      => null,
                'statut'             => 'consulte',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // Résultat 5 : NFS patient 1
            [
                'demande_analyse_id' => $demandeId('NFS'),
                'dossier_medical_id' => $dossierId('DOS-2024-001'),
                'laborantin_id'      => $laborantinId('AGR-LAB-2024-002'),
                'type_analyse'       => 'NFS + Ionogramme',
                'date_prelevement'   => '2024-10-07',
                'date_resultat'      => '2024-10-08',
                'resultats'          => json_encode([
                    'Hémoglobine'    => '14,2 g/dL',
                    'Globules rouges'=> '4,8 M/mm³',
                    'Globules blancs'=> '7 200/mm³',
                    'Plaquettes'     => '230 000/mm³',
                    'Sodium'         => '142 mmol/L',
                    'Potassium'      => '4,1 mmol/L',
                    'Chlore'         => '104 mmol/L',
                    'Créatinine'     => '82 µmol/L',
                ]),
                'valeur_normale'     => json_encode([
                    'Hémoglobine' => '13-17 g/dL',
                    'GB'          => '4000-10000/mm³',
                    'Sodium'      => '135-145 mmol/L',
                    'Potassium'   => '3,5-5,0 mmol/L',
                    'Créatinine'  => '60-110 µmol/L',
                ]),
                'interpretation'     => 'NFS normale. Ionogramme sans anomalie. Fonction rénale conservée. Pas de contre-indication au traitement HTA.',
                'fichier_joint'      => null,
                'statut'             => 'disponible',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('resultats_analyses')->insert($resultats);
    }
}
