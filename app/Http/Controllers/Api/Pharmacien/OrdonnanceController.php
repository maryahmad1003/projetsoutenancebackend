<?php

namespace App\Http\Controllers\Api\Pharmacien;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class OrdonnanceController extends Controller
{
    public function index(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;

        $ordonnances = Prescription::where('pharmacie_id', $pharmacien->pharmacie_id)
            ->with(['medecin.user', 'medicaments', 'consultation.dossierMedical.patient.user'])
            ->orderBy('date_emission', 'desc')
            ->paginate(20);

        return response()->json($ordonnances);
    }

    public function show(string $id)
    {
        $ordonnance = Prescription::with([
            'medecin.user',
            'medicaments',
            'consultation.dossierMedical.patient.user',
            'pharmacie'
        ])->findOrFail($id);

        return response()->json($ordonnance);
    }
}