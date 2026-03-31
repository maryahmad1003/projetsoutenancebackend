<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CentreSante;
use Illuminate\Http\Request;

class CentreSanteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/centres-sante",
     *     tags={"Admin - Centres de santé"},
     *     summary="Lister les centres de santé",
     *     description="Retourne la liste paginée des centres de santé avec le nombre de médecins. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des centres de santé", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index()
    {
        $centres = CentreSante::with(['medecins.user'])->withCount('medecins')->paginate(20);
        return response()->json($centres);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/centres-sante",
     *     tags={"Admin - Centres de santé"},
     *     summary="Créer un centre de santé",
     *     description="Ajoute un nouveau centre de santé. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","adresse","type","region"},
     *             @OA\Property(property="nom", type="string", example="Hôpital Principal de Dakar"),
     *             @OA\Property(property="adresse", type="string", example="Avenue Cheikh Anta Diop, Dakar"),
     *             @OA\Property(property="type", type="string", enum={"hopital","clinique","centre_sante","poste_sante"}, example="hopital"),
     *             @OA\Property(property="region", type="string", example="Dakar"),
     *             @OA\Property(property="telephone", type="string", example="+221338234567"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@hpd.sn"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Centre créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Centre de santé créé"),
     *             @OA\Property(property="centre", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'required|string',
            'type' => 'required|in:hopital,clinique,centre_sante,poste_sante',
            'region' => 'required|string',
        ]);

        $centre = CentreSante::create($request->all());
        return response()->json(['message' => 'Centre de santé créé', 'centre' => $centre], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/centres-sante/{id}",
     *     tags={"Admin - Centres de santé"},
     *     summary="Détails d'un centre de santé",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails du centre", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Centre non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $centre = CentreSante::with(['medecins.user'])->withCount('medecins')->findOrFail($id);
        return response()->json($centre);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/centres-sante/{id}",
     *     tags={"Admin - Centres de santé"},
     *     summary="Modifier un centre de santé",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="type", type="string", enum={"hopital","clinique","centre_sante","poste_sante"}),
     *             @OA\Property(property="region", type="string"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Centre modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Centre modifié"),
     *             @OA\Property(property="centre", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Centre non trouvé")
     * )
     */
    public function update(Request $request, string $id)
    {
        $centre = CentreSante::findOrFail($id);
        $centre->update($request->all());
        return response()->json(['message' => 'Centre modifié', 'centre' => $centre]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/centres-sante/{id}",
     *     tags={"Admin - Centres de santé"},
     *     summary="Supprimer un centre de santé",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Centre supprimé",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Centre supprimé"))
     *     ),
     *     @OA\Response(response=404, description="Centre non trouvé")
     * )
     */
    public function destroy(string $id)
    {
        CentreSante::findOrFail($id)->delete();
        return response()->json(['message' => 'Centre supprimé']);
    }
}
