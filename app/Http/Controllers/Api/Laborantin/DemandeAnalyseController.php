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
        $demandes = DemandeAnalyse::where('laboratoire_id', $laborantin->laboratoire_id)
            ->with(['patient.user', 'medecin.user', 'resultat'])
            ->orderBy('urgence', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($demandes);
    }

    public function show(string $id)
    {
        $demande = DemandeAnalyse::with(['patient.user', 'medecin.user', 'laboratoire', 'resultat'])->findOrFail($id);
        return response()->json($demande);
    }
}