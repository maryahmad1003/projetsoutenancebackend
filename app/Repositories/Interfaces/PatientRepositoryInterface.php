<?php

namespace App\Repositories\Interfaces;

interface PatientRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 15);
    public function find(int $id);
    public function findByMedecin(int $medecinId, array $filters = [], int $perPage = 15);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
