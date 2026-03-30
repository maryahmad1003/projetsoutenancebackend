<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarnetVaccinationSeeder extends Seeder
{
    public function run(): void
    {
        $patients = DB::table('patients')->orderBy('id')->pluck('id');

        $carnets = $patients->map(fn($patientId) => [
            'patient_id' => $patientId,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('carnets_vaccination')->insert($carnets);
    }
}
