<?php

namespace App\Repositories\Interfaces;

interface MessageRepositoryInterface
{
    public function getConversations(int $userId): array;
    public function getMessages(int $userId, int $interlocuteurId, int $perPage = 50);
    public function create(array $data);
    public function markAsRead(int $expediteurId, int $destinataireId): void;
    public function countUnread(int $userId): int;
}
