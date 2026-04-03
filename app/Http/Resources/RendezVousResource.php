<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RendezVousResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date_heure' => $this->date_heure,
            'duree'      => $this->duree,
            'motif'      => $this->motif,
            'statut'     => $this->statut,
            'type'       => $this->type,
            'created_at' => $this->created_at?->toDateTimeString(),

            'patient' => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'medecin' => $this->whenLoaded('medecin', fn () => new MedecinResource($this->medecin)),
        ];
    }
}
