<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\CarnetVaccination;
use App\Models\Vaccin;
use Illuminate\Http\Request;

class CarnetVaccinationController extends Controller
{
    public function monCarnet(Request $request)
    {
        $patient = $request->user()->patient;
        $carnet = CarnetVaccination::where('patient_id', $patient->id)
            ->with(['vaccins.medecin.user'])
            ->first();

        return response()->json($carnet);
    }

    public function ajouterVaccin(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'nom' => 'required|string',
            'date_administration' => 'required|date',
            'date_rappel' => 'nullable|date',
            'lot' => 'nullable|string',
        ]);

        $carnet = CarnetVaccination::where('patient_id', $request->patient_id)->first();

        $vaccin = Vaccin::create([
            'carnet_vaccination_id' => $carnet->id,
            'medecin_id' => $request->user()->medecin->id ?? null,
            'nom' => $request->nom,
            'date_administration' => $request->date_administration,
            'date_rappel' => $request->date_rappel,
            'lot' => $request->lot,
        ]);

        return response()->json(['message' => 'Vaccin ajouté', 'vaccin' => $vaccin], 201);
    }
}