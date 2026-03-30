<?php

namespace App\Repositories\Eloquent;

use App\Models\Message;
use App\Repositories\Interfaces\MessageRepositoryInterface;

class MessageRepository implements MessageRepositoryInterface
{
    public function getConversations(int $userId): array
    {
        $messages = Message::where('expediteur_id', $userId)
            ->orWhere('destinataire_id', $userId)
            ->with(['expediteur', 'destinataire'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $messages
            ->groupBy(fn ($m) => $m->expediteur_id === $userId ? $m->destinataire_id : $m->expediteur_id)
            ->map(function ($msgs, $interlocuteurId) use ($userId) {
                $dernier       = $msgs->first();
                $interlocuteur = $dernier->expediteur_id === $userId ? $dernier->destinataire : $dernier->expediteur;
                return [
                    'interlocuteur_id'   => $interlocuteurId,
                    'interlocuteur'      => [
                        'id'     => $interlocuteur->id,
                        'nom'    => $interlocuteur->nom,
                        'prenom' => $interlocuteur->prenom,
                        'role'   => $interlocuteur->role,
                        'photo_profil' => $interlocuteur->photo_profil,
                    ],
                    'dernier_message'    => $dernier->contenu,
                    'dernier_message_at' => $dernier->created_at,
                    'non_lus'            => $msgs->where('destinataire_id', $userId)->where('lu', false)->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getMessages(int $userId, int $interlocuteurId, int $perPage = 50)
    {
        return Message::where(fn ($q) => $q->where('expediteur_id', $userId)->where('destinataire_id', $interlocuteurId))
            ->orWhere(fn ($q) => $q->where('expediteur_id', $interlocuteurId)->where('destinataire_id', $userId))
            ->with(['expediteur:id,nom,prenom,role,photo_profil'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Message::create($data);
    }

    public function markAsRead(int $expediteurId, int $destinataireId): void
    {
        Message::where('expediteur_id', $expediteurId)
            ->where('destinataire_id', $destinataireId)
            ->where('lu', false)
            ->update(['lu' => true, 'lu_at' => now()]);
    }

    public function countUnread(int $userId): int
    {
        return Message::where('destinataire_id', $userId)->where('lu', false)->count();
    }
}
