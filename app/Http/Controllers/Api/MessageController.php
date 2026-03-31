<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/messages/conversations",
     *     tags={"Messagerie"},
     *     summary="Liste des conversations",
     *     description="Retourne un résumé de chaque conversation (dernier message, interlocuteur, nombre de non lus).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des conversations",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="interlocuteur_id", type="integer"),
     *                 @OA\Property(property="interlocuteur", type="object"),
     *                 @OA\Property(property="dernier_message", type="string"),
     *                 @OA\Property(property="dernier_message_at", type="string", format="date-time"),
     *                 @OA\Property(property="non_lus", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Message::where('expediteur_id', $userId)
            ->orWhere('destinataire_id', $userId)
            ->with(['expediteur', 'destinataire'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->expediteur_id === $userId
                    ? $message->destinataire_id
                    : $message->expediteur_id;
            })
            ->map(function ($messages, $interlocuteurId) use ($userId) {
                $dernier    = $messages->first();
                $interlocuteur = $dernier->expediteur_id === $userId
                    ? $dernier->destinataire
                    : $dernier->expediteur;

                return [
                    'interlocuteur_id'  => $interlocuteurId,
                    'interlocuteur'     => [
                        'id'     => $interlocuteur->id,
                        'nom'    => $interlocuteur->nom,
                        'prenom' => $interlocuteur->prenom,
                        'role'   => $interlocuteur->role,
                        'photo_profil' => $interlocuteur->photo_profil,
                    ],
                    'dernier_message'   => $dernier->contenu,
                    'dernier_message_at'=> $dernier->created_at,
                    'non_lus'           => $messages->where('destinataire_id', $userId)->where('lu', false)->count(),
                ];
            })
            ->values();

        return response()->json($conversations);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/{userId}",
     *     tags={"Messagerie"},
     *     summary="Messages d'une conversation",
     *     description="Retourne les messages échangés avec un utilisateur donné, et marque les messages reçus comme lus.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, description="ID de l'interlocuteur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", description="Nombre de messages par page",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(response=200, description="Messages de la conversation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request, int $userId)
    {
        $moi = $request->user()->id;

        $messages = Message::where(function ($q) use ($moi, $userId) {
                $q->where('expediteur_id', $moi)->where('destinataire_id', $userId);
            })
            ->orWhere(function ($q) use ($moi, $userId) {
                $q->where('expediteur_id', $userId)->where('destinataire_id', $moi);
            })
            ->with(['expediteur:id,nom,prenom,role,photo_profil'])
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 50));

        Message::where('expediteur_id', $userId)
            ->where('destinataire_id', $moi)
            ->where('lu', false)
            ->update(['lu' => true, 'lu_at' => now()]);

        return response()->json($messages);
    }

    /**
     * @OA\Post(
     *     path="/api/messages",
     *     tags={"Messagerie"},
     *     summary="Envoyer un message",
     *     description="Envoie un message texte ou avec pièce jointe (image, PDF, doc). Utiliser multipart/form-data pour l'envoi de fichier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"destinataire_id"},
     *                 @OA\Property(property="destinataire_id", type="integer", example=5),
     *                 @OA\Property(property="contenu", type="string", example="Bonjour docteur, j'ai une question."),
     *                 @OA\Property(property="fichier", type="string", format="binary", description="Fichier (jpg, png, pdf, doc, docx) max 5 Mo")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Message envoyé", @OA\JsonContent(type="object")),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'destinataire_id' => 'required|exists:users,id',
            'contenu'         => 'required_without:fichier|nullable|string|max:2000',
            'fichier'         => 'sometimes|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $data = [
            'expediteur_id'   => $request->user()->id,
            'destinataire_id' => $request->destinataire_id,
            'contenu'         => $request->contenu ?? '',
            'type'            => 'texte',
        ];

        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('messages_fichiers', 'public');
            $data['fichier_url'] = $path;
            $data['type']        = str_starts_with($request->file('fichier')->getMimeType(), 'image/') ? 'image' : 'fichier';
        }

        $message = Message::create($data);
        $message->load('expediteur:id,nom,prenom,role,photo_profil');

        return response()->json($message, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/non-lus",
     *     tags={"Messagerie"},
     *     summary="Nombre de messages non lus",
     *     description="Retourne le compteur de messages non lus pour l'utilisateur connecté (utile pour la barre de navigation).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Compteur de messages non lus",
     *         @OA\JsonContent(@OA\Property(property="non_lus", type="integer", example=4))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function nonLus(Request $request)
    {
        $count = Message::where('destinataire_id', $request->user()->id)
            ->where('lu', false)
            ->count();

        return response()->json(['non_lus' => $count]);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/contacts",
     *     tags={"Messagerie"},
     *     summary="Liste des contacts disponibles",
     *     description="Retourne la liste des utilisateurs avec qui l'utilisateur connecté peut échanger, selon son rôle.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des contacts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nom", type="string"),
     *                 @OA\Property(property="prenom", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="photo_profil", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function contacts(Request $request)
    {
        $user = $request->user();

        $rolesAccessibles = match ($user->role) {
            'medecin'        => ['patient', 'pharmacien', 'laborantin', 'administrateur'],
            'patient'        => ['medecin', 'pharmacien', 'administrateur'],
            'pharmacien'     => ['medecin', 'patient', 'administrateur'],
            'laborantin'     => ['medecin', 'administrateur'],
            'administrateur' => ['medecin', 'patient', 'pharmacien', 'laborantin', 'administrateur'],
            default          => [],
        };

        $contacts = User::whereIn('role', $rolesAccessibles)
            ->where('id', '!=', $user->id)
            ->where('est_actif', true)
            ->select('id', 'nom', 'prenom', 'role', 'photo_profil')
            ->orderBy('nom')
            ->get();

        return response()->json($contacts);
    }
}
