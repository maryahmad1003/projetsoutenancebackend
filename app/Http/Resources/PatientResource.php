<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'num_dossier'            => $this->num_dossier,
            'nom'                    => $this->user?->nom,
            'prenom'                 => $this->user?->prenom,
            'email'                  => $this->user?->email,
            'telephone'              => $this->user?->telephone,
            'date_naissance'         => $this->date_naissance,
            'age'                    => $this->age,
            'sexe'                   => $this->sexe,
            'adresse'                => $this->adresse,
            'groupe_sanguin'         => $this->groupe_sanguin,
            'taille'                 => $this->taille,
            'poids'                  => $this->poids,
            'profession'             => $this->profession,
            'situation_matrimoniale' => $this->situation_matrimoniale,
            'nombre_enfants'         => $this->nombre_enfants,
            'antecedents_medicaux'   => $this->antecedents_medicaux,
            'antecedents_chirurgicaux'=> $this->antecedents_chirurgicaux,
            'antecedents_familiaux'  => $this->antecedents_familiaux,
            'allergies'              => $this->allergies,
            'traitement_en_cours'    => $this->traitement_en_cours,
            'assurance'              => $this->assurance,
            'numero_assurance'       => $this->numero_assurance,
            'personne_contact'       => $this->personne_contact,
            'tel_contact'            => $this->tel_contact,
            'qr_code'                => $this->qr_code,

            // Relations (chargées si présentes)
            'dossier_medical' => $this->whenLoaded('dossierMedical', fn () => new DossierMedicalResource($this->dossierMedical)),
            'medecin_traitant'=> $this->whenLoaded('medecin',        fn () => new MedecinResource($this->medecin)),
        ];
    }
}
