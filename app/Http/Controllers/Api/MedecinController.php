<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medecin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedecinController extends Controller
{
    public function liste(Request $request): JsonResponse
    {
        $request->validate([
            'specialite' => 'nullable|string',
            'centre_id' => 'nullable|exists:centres_sante,id',
        ]);

        $query = Medecin::with(['user', 'centreSante'])
            ->where('accepte_rdv_en_ligne', true);

        if ($request->filled('specialite')) {
            $query->where('specialite', 'like', '%' . $request->specialite . '%');
        }

        if ($request->filled('centre_id')) {
            $query->where('centre_sante_id', $request->centre_id);
        }

        $medecins = $query->get()->map(function ($medecin) {
            return [
                'id' => $medecin->id,
                'nom' => 'Dr. ' . $medecin->user->nom,
                'prenom' => $medecin->user->prenom,
                'specialite' => $medecin->specialite,
                'centre' => $medecin->centreSante?->nom,
                'horaires' => $medecin->horaires ?? $medecin->getDefaultHoraires(),
            ];
        });

        return response()->json($medecins);
    }
}
