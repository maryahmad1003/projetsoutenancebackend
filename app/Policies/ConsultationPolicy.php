<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    /**
     * Seul un médecin peut lister les consultations.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'medecin';
    }

    /**
     * Un médecin peut voir une consultation s'il en est l'auteur.
     * Un patient peut voir ses propres consultations via son dossier.
     */
    public function view(User $user, Consultation $consultation): bool
    {
        if ($user->role === 'medecin') {
            return $consultation->medecin_id === $user->medecin?->id;
        }

        if ($user->role === 'patient') {
            $dossierId = $user->patient?->dossierMedical?->id;
            return $consultation->dossier_medical_id === $dossierId;
        }

        return false;
    }

    /**
     * Seul un médecin peut créer une consultation.
     */
    public function create(User $user): bool
    {
        return $user->role === 'medecin';
    }

    /**
     * Seul le médecin auteur peut modifier sa consultation.
     */
    public function update(User $user, Consultation $consultation): bool
    {
        return $user->role === 'medecin'
            && $consultation->medecin_id === $user->medecin?->id;
    }

    /**
     * Seul le médecin auteur peut supprimer sa consultation.
     */
    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->role === 'medecin'
            && $consultation->medecin_id === $user->medecin?->id;
    }
}
