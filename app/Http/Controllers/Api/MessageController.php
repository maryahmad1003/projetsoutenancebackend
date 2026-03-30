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
     * Liste des conversations (un résumé par interlocuteur)
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        // Récupérer le dernier message de chaque conversation
        $conversations = Message::where('expediteur_id', $userId)
            ->orWhere('destinataire_id', $userId)
            ->with(['expediteur', 'destinataire'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                // Clé = l'interlocuteur (pas soi-même)
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
     * Messages d'une conversation avec un utilisateur
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

        // Marquer comme lus les messages reçus
        Message::where('expediteur_id', $userId)
            ->where('destinataire_id', $moi)
            ->where('lu', false)
            ->update(['lu' => true, 'lu_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Envoyer un message
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
     * Nombre de messages non lus (pour la navbar)
     */
    public function nonLus(Request $request)
    {
        $count = Message::where('destinataire_id', $request->user()->id)
            ->where('lu', false)
            ->count();

        return response()->json(['non_lus' => $count]);
    }

    /**
     * Liste des utilisateurs avec qui on peut échanger
     * (selon les rôles — le médecin peut parler à ses patients, etc.)
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
