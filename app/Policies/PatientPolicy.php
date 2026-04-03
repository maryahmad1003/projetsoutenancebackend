<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Médecins et admins peuvent lister tous les patients.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['medecin', 'administrateur']);
    }

    /**
     * - Médecin : peut voir n'importe quel patient.
     * - Patient : ne peut voir que son propre profil.
     * - Admin : accès total.
     */
    public function view(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            'medecin'        => true,
            'administrateur' => true,
            'patient'        => $patient->user_id === $user->id,
            default          => false,
        };
    }

    /**
     * Seul un médecin peut créer un dossier patient.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['medecin', 'administrateur']);
    }

    /**
     * Un médecin peut mettre à jour un patient.
     * Un patient peut mettre à jour son propre profil.
     */
    public function update(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            'medecin'        => true,
            'administrateur' => true,
            'patient'        => $patient->user_id === $user->id,
            default          => false,
        };
    }

    /**
     * Seul un admin peut désactiver/supprimer un patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->role === 'administrateur';
    }
}
