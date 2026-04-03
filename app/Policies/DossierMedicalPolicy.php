<?php

namespace App\Policies;

use App\Models\DossierMedical;
use App\Models\User;

class DossierMedicalPolicy
{
    /**
     * Un médecin peut voir n'importe quel dossier.
     * Un patient ne peut voir que le sien.
     */
    public function view(User $user, DossierMedical $dossier): bool
    {
        return match ($user->role) {
            'medecin'        => true,
            'administrateur' => true,
            'patient'        => $dossier->patient?->user_id === $user->id,
            default          => false,
        };
    }

    /**
     * Seul un médecin peut mettre à jour un dossier médical.
     */
    public function update(User $user, DossierMedical $dossier): bool
    {
        return $user->role === 'medecin';
    }
}
