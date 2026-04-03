<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'date'             => $this->date,
            'motif'            => $this->motif,
            'diagnostic'       => $this->diagnostic,
            'notes'            => $this->notes,
            'type_consultation'=> $this->type_consultation,
            'urgence'          => $this->urgence,
            'recommandations'  => $this->recommandations,
            'prochain_rdv'     => $this->prochain_rdv,
            'created_at'       => $this->created_at?->toDateTimeString(),

            // Constantes vitales
            'constantes' => [
                'tension'               => $this->tension,
                'poids'                 => $this->poids,
                'taille'                => $this->taille,
                'temperature'           => $this->temperature,
                'frequence_cardiaque'   => $this->frequence_cardiaque,
                'glycemie'              => $this->glycemie,
                'imc'                   => $this->imc,
                'saturation_oxygene'    => $this->saturation_oxygene,
                'frequence_respiratoire'=> $this->frequence_respiratoire,
            ],

            // Informations cliniques
            'clinique' => [
                'examen_clinique'        => $this->examen_clinique,
                'antecedents_signales'   => $this->antecedents_signales,
                'allergies_signalees'    => $this->allergies_signalees,
                'traitement_en_cours'    => $this->traitement_en_cours,
            ],

            // Grossesse (si applicable)
            'grossesse' => $this->when($this->est_enceinte, [
                'semaines'               => $this->semaines_grossesse,
                'date_derniere_regle'    => $this->date_derniere_regle,
                'date_accouchement_prevu'=> $this->date_accouchement_prevue,
                'groupe_sanguin'         => $this->groupe_sanguin_grossesse,
                'observations'           => $this->observations_grossesse,
            ]),

            // Relations
            'medecin'       => $this->whenLoaded('medecin',      fn () => new MedecinResource($this->medecin)),
            'prescriptions' => $this->whenLoaded('prescriptions',fn () => PrescriptionResource::collection($this->prescriptions)),
        ];
    }
}
