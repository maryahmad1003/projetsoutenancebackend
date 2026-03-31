<?php

namespace App\Http\Controllers\Api\Pharmacien;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class OrdonnanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/pharmacien/ordonnances",
     *     tags={"Pharmacien - Ordonnances"},
     *     summary="Lister les ordonnances de la pharmacie",
     *     description="Retourne la liste paginée des ordonnances envoyées à la pharmacie du pharmacien connecté, avec filtres.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="statut", in="query", description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"active","envoyee","delivree","expiree"})
     *     ),
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom du patient ou du médecin",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="date_debut", in="query", description="Date d'émission min (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(name="date_fin", in="query", description="Date d'émission max (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Liste des ordonnances paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/pharmacien/ordonnances/{id}",
     *     tags={"Pharmacien - Ordonnances"},
     *     summary="Détails d'une ordonnance",
     *     description="Retourne les détails complets d'une ordonnance avec les médicaments prescrits.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de l'ordonnance",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'ordonnance", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Ordonnance non trouvée"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
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
