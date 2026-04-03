<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedecinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nom'           => $this->user?->nom,
            'prenom'        => $this->user?->prenom,
            'email'         => $this->user?->email,
            'telephone'     => $this->user?->telephone,
            'matricule'     => $this->matricule,
            'specialite'    => $this->specialite,
            'num_ordre'     => $this->num_ordre,
            'centre_sante'  => $this->whenLoaded('centreSante', fn () => [
                'id'   => $this->centreSante?->id,
                'nom'  => $this->centreSante?->nom,
                'type' => $this->centreSante?->type,
            ]),
        ];
    }
}
