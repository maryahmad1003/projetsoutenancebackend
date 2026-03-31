<?php

namespace App\Http\Controllers\Api\Laborantin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAnalyse;
use Illuminate\Http\Request;

class DemandeAnalyseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/laborantin/demandes",
     *     tags={"Laborantin - Demandes"},
     *     summary="Lister les demandes d'analyse du laboratoire",
     *     description="Retourne la liste paginée des demandes d'analyse reçues par le laboratoire du laborantin connecté, triées par urgence puis par date.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="statut", in="query", description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"envoyee","en_cours","terminee"})
     *     ),
     *     @OA\Parameter(name="urgence", in="query", description="Filtrer par urgence (true/false)",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom du patient ou type d'analyse",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Liste des demandes paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/laborantin/demandes/{id}",
     *     tags={"Laborantin - Demandes"},
     *     summary="Détails d'une demande d'analyse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la demande", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Non trouvée"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $demande = DemandeAnalyse::with(['patient.user', 'medecin.user', 'laboratoire', 'resultat'])->findOrFail($id);
        return response()->json($demande);
    }
}
