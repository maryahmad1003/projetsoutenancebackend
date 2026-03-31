<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campagne;
use App\Models\Administrateur;
use Illuminate\Http\Request;

class CampagneController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/campagnes",
     *     tags={"Admin - Campagnes"},
     *     summary="Lister les campagnes de santé",
     *     description="Retourne la liste paginée des campagnes, triées par date de début décroissante. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des campagnes", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index()
    {
        $campagnes = Campagne::with('administrateur.user')->orderBy('date_debut', 'desc')->paginate(20);
        return response()->json($campagnes);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/campagnes",
     *     tags={"Admin - Campagnes"},
     *     summary="Créer une campagne de santé",
     *     description="Crée une nouvelle campagne (prévention, vaccination ou sensibilisation). Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"titre","date_debut","type"},
     *             @OA\Property(property="titre", type="string", example="Campagne de vaccination contre la polio"),
     *             @OA\Property(property="description", type="string", example="Campagne nationale de vaccination"),
     *             @OA\Property(property="date_debut", type="string", format="date", example="2026-04-01"),
     *             @OA\Property(property="date_fin", type="string", format="date", example="2026-04-30"),
     *             @OA\Property(property="cible", type="string", example="Enfants de 0 à 5 ans"),
     *             @OA\Property(property="region", type="string", example="Dakar"),
     *             @OA\Property(property="type", type="string", enum={"prevention","vaccination","sensibilisation"}, example="vaccination")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Campagne créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campagne créée"),
     *             @OA\Property(property="campagne", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'cible' => 'nullable|string',
            'region' => 'nullable|string',
            'type' => 'required|in:prevention,vaccination,sensibilisation',
        ]);

        $administrateur = Administrateur::firstOrCreate(['user_id' => $request->user()->id]);

        $campagne = Campagne::create([
            'administrateur_id' => $administrateur->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'cible' => $request->cible,
            'region' => $request->region,
            'type' => $request->type,
        ]);

        return response()->json(['message' => 'Campagne créée', 'campagne' => $campagne], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/campagnes/{id}",
     *     tags={"Admin - Campagnes"},
     *     summary="Détails d'une campagne",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la campagne", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Campagne non trouvée")
     * )
     */
    public function show(string $id)
    {
        $campagne = Campagne::with('administrateur.user')->findOrFail($id);
        return response()->json($campagne);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/campagnes/{id}",
     *     tags={"Admin - Campagnes"},
     *     summary="Modifier une campagne",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="titre", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="date_debut", type="string", format="date"),
     *             @OA\Property(property="date_fin", type="string", format="date"),
     *             @OA\Property(property="cible", type="string"),
     *             @OA\Property(property="region", type="string"),
     *             @OA\Property(property="type", type="string", enum={"prevention","vaccination","sensibilisation"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Campagne modifiée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campagne modifiée"),
     *             @OA\Property(property="campagne", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Campagne non trouvée")
     * )
     */
    public function update(Request $request, string $id)
    {
        $campagne = Campagne::findOrFail($id);
        $campagne->update($request->all());
        return response()->json(['message' => 'Campagne modifiée', 'campagne' => $campagne]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/campagnes/{id}",
     *     tags={"Admin - Campagnes"},
     *     summary="Supprimer une campagne",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Campagne supprimée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Campagne supprimée"))
     *     ),
     *     @OA\Response(response=404, description="Campagne non trouvée")
     * )
     */
    public function destroy(string $id)
    {
        Campagne::findOrFail($id)->delete();
        return response()->json(['message' => 'Campagne supprimée']);
    }
}
