<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Médecin (les siennes) ou pharmacien (reçues) peuvent lister.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['medecin', 'pharmacien', 'patient']);
    }

    /**
     * - Médecin : seulement ses prescriptions.
     * - Pharmacien : prescriptions envoyées à sa pharmacie.
     * - Patient : ses propres prescriptions.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        return match ($user->role) {
            'medecin'    => $prescription->medecin_id === $user->medecin?->id,
            'pharmacien' => $prescription->pharmacie_id === $user->pharmacien?->pharmacie_id,
            'patient'    => $prescription->consultation?->dossierMedical?->patient_id === $user->patient?->id,
            default      => false,
        };
    }

    /**
     * Seul un médecin peut créer une prescription.
     */
    public function create(User $user): bool
    {
        return $user->role === 'medecin';
    }

    /**
     * Seul le médecin auteur peut modifier une prescription non encore délivrée.
     */
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->role === 'medecin'
            && $prescription->medecin_id === $user->medecin?->id
            && $prescription->statut === 'active';
    }

    /**
     * Un pharmacien peut marquer une prescription comme délivrée.
     */
    public function deliver(User $user, Prescription $prescription): bool
    {
        return $user->role === 'pharmacien'
            && in_array($prescription->statut, ['active', 'envoyee']);
    }

    /**
     * Seul le médecin auteur peut supprimer une prescription active.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->role === 'medecin'
            && $prescription->medecin_id === $user->medecin?->id
            && $prescription->statut === 'active';
    }
}
