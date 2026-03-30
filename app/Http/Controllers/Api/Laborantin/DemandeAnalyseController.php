<?php

namespace App\Http\Controllers\Api\Laborantin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAnalyse;
use Illuminate\Http\Request;

class DemandeAnalyseController extends Controller
{
    public function index(Request $request)
    {
        $laborantin = $request->user()->laborantin;

        $query = DemandeAnalyse::where('laboratoire_id', $laborantin->laboratoire_id)
            ->with(['patient.user', 'medecin.user', 'resultat']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('urgence')) {
            $query->where('urgence', $request->urgence);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            })->orWhere('type_analyse', 'like', "%{$search}%");
        }

        return response()->json(
            $query->orderBy('urgence', 'desc')
                  ->orderBy('created_at', 'desc')
                  ->paginate($request->get('per_page', 20))
        );
    }

    public function show(string $id)
    {
        $demande = DemandeAnalyse::with(['patient.user', 'medecin.user', 'laboratoire', 'resultat'])->findOrFail($id);
        return response()->json($demande);
    }
}