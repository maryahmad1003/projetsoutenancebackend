<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PharmacieSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacies = [
            [
                'nom'        => 'Pharmacie du Plateau',
                'adresse'    => 'Avenue Léopold Sédar Senghor, Plateau, Dakar',
                'telephone'  => '+221338211050',
                'horaires'   => 'Lun-Sam: 08h-21h, Dim: 09h-14h',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'        => 'Pharmacie Dieye',
                'adresse'    => 'Rue de Thiong, Dakar',
                'telephone'  => '+221338232200',
                'horaires'   => 'Lun-Sam: 07h30-22h, Dim: 08h-13h',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'        => 'Pharmacie de la Médina',
                'adresse'    => 'Boulevard de la Gueule Tapée, Médina, Dakar',
                'telephone'  => '+221338243300',
                'horaires'   => 'Lun-Sam: 08h-20h, Dim: 09h-13h',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'        => 'Pharmacie Keur Mbaye Fall',
                'adresse'    => 'Route de Rufisque, Pikine',
                'telephone'  => '+221338547700',
                'horaires'   => 'Lun-Sam: 08h-21h, Dim: 09h-14h',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom'        => 'Pharmacie de Thiès Centrale',
                'adresse'    => 'Avenue Blaise Diagne, Thiès',
                'telephone'  => '+221339511220',
                'horaires'   => 'Lun-Sam: 08h-20h30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('pharmacies')->insert($pharmacies);
    }
}
