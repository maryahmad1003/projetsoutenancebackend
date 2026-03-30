<?php

namespace App\Repositories\Interfaces;

interface ConsultationRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 20);
    public function find(int $id);
    public function findByMedecin(int $medecinId, array $filters = [], int $perPage = 20);
    public function findByDossier(int $dossierId, array $filters = [], int $perPage = 10);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
