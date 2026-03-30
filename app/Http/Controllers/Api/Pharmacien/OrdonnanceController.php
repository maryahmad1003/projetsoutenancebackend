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

        $query = Prescription::where('pharmacie_id', $pharmacien->pharmacie_id)
            ->with(['medecin.user', 'medicaments', 'consultation.dossierMedical.patient.user']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('consultation.dossierMedical.patient.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            })->orWhereHas('medecin.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_emission', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_emission', '<=', $request->date_fin);
        }

        return response()->json($query->orderBy('date_emission', 'desc')->paginate($request->get('per_page', 20)));
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