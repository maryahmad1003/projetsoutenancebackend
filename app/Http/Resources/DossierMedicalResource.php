<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DossierMedicalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero_dossier' => $this->numero_dossier,
            'antecedents'    => $this->antecedents,
            'allergies'      => $this->allergies,
            'notes_generales'=> $this->notes_generales,
            'est_archive'    => $this->est_archive,
            'created_at'     => $this->created_at?->toDateTimeString(),

            // Relations
            'patient'       => $this->whenLoaded('patient',       fn () => new PatientResource($this->patient)),
            'consultations' => $this->whenLoaded('consultations', fn () => ConsultationResource::collection($this->consultations)),
        ];
    }
}
