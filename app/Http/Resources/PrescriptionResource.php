<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'numero'          => $this->numero,
            'date_emission'   => $this->date_emission,
            'date_expiration' => $this->date_expiration,
            'statut'          => $this->statut,
            'notes'           => $this->notes,
            'created_at'      => $this->created_at?->toDateTimeString(),

            // Médicaments avec posologie (table pivot)
            'medicaments' => $this->whenLoaded('medicaments', fn () =>
                $this->medicaments->map(fn ($m) => [
                    'id'              => $m->id,
                    'nom'             => $m->nom,
                    'dosage'          => $m->dosage,
                    'forme'           => $m->forme,
                    'posologie'       => $m->pivot->posologie,
                    'duree_traitement'=> $m->pivot->duree_traitement,
                    'quantite'        => $m->pivot->quantite,
                ])
            ),

            // Relations
            'medecin'    => $this->whenLoaded('medecin',      fn () => new MedecinResource($this->medecin)),
            'pharmacie'  => $this->whenLoaded('pharmacie',    fn () => [
                'id'  => $this->pharmacie?->id,
                'nom' => $this->pharmacie?->nom,
            ]),
        ];
    }
}
