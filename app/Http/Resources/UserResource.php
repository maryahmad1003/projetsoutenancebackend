<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nom'          => $this->nom,
            'prenom'       => $this->prenom,
            'email'        => $this->email,
            'telephone'    => $this->telephone,
            'role'         => $this->role,
            'langue'       => $this->langue,
            'est_actif'    => $this->est_actif,
            'photo_profil' => $this->photo_profil
                ? asset('storage/' . $this->photo_profil)
                : null,
            'created_at'   => $this->created_at?->toDateTimeString(),

            // Relations conditionnelles (chargées si présentes)
            'patient'       => $this->whenLoaded('patient',       fn () => new PatientResource($this->patient)),
            'medecin'       => $this->whenLoaded('medecin',       fn () => new MedecinResource($this->medecin)),
            'administrateur'=> $this->whenLoaded('administrateur',fn () => $this->administrateur),
            'pharmacien'    => $this->whenLoaded('pharmacien',    fn () => $this->pharmacien),
            'laborantin'    => $this->whenLoaded('laborantin',    fn () => $this->laborantin),
        ];
    }
}
