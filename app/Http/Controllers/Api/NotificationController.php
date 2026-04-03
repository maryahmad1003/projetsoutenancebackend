<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Lister les notifications de l'utilisateur connecté",
     *     description="Retourne la liste paginée des notifications et le nombre de notifications non lues.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", description="Numéro de page", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications et compteur non lus",
     *         @OA\JsonContent(
     *             @OA\Property(property="notifications", type="object", description="Liste paginée"),
     *             @OA\Property(property="non_lues", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $nonLues = Notification::where('user_id', $request->user()->id)->where('est_lue', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'non_lues' => $nonLues,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Détails d'une notification",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function show(string $id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{id}/lue",
     *     tags={"Notifications"},
     *     summary="Marquer une notification comme lue",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la notification",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marquée comme lue",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Notification marquée comme lue"))
     *     ),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function marquerLue(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['est_lue' => true]);
        return response()->json(['message' => 'Notification marquée comme lue']);
    }

    public function update(Request $request, string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['est_lue' => true]);
        return response()->json(['message' => 'Notification mise à jour']);
    }

    public function marquerToutesLues(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('est_lue', false)
            ->update(['est_lue' => true]);

        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    }

    public function supprimer(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification supprimée']);
    }
}
