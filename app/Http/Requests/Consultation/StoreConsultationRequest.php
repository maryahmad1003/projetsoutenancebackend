<?php

namespace App\Http\Requests\Consultation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un médecin connecté peut créer une consultation
        return $this->user()?->role === 'medecin';
    }

    public function rules(): array
    {
        return [
            'patient_id'          => ['required', 'exists:patients,id'],
            'motif'               => ['required', 'string', 'max:1000'],
            'diagnostic'          => ['nullable', 'string', 'max:2000'],
            'notes'               => ['nullable', 'string'],
            'type_consultation'   => ['nullable', 'in:consultation,suivi,urgence,teleconsultation'],
            'urgence'             => ['nullable', 'boolean'],
            'recommandations'     => ['nullable', 'string'],
            'prochain_rdv'        => ['nullable', 'date', 'after:today'],

            // Constantes vitales
            'tension'              => ['nullable', 'string', 'max:20'],
            'poids'                => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'taille'               => ['nullable', 'numeric', 'min:20', 'max:300'],
            'temperature'          => ['nullable', 'numeric', 'min:30', 'max:45'],
            'frequence_cardiaque'  => ['nullable', 'integer', 'min:20', 'max:300'],
            'glycemie'             => ['nullable', 'numeric', 'min:0'],
            'saturation_oxygene'   => ['nullable', 'integer', 'min:50', 'max:100'],
            'frequence_respiratoire'=>['nullable', 'integer', 'min:5', 'max:100'],

            // Infos cliniques
            'examen_clinique'      => ['nullable', 'string'],
            'antecedents_signales' => ['nullable', 'string'],
            'allergies_signalees'  => ['nullable', 'string'],
            'traitement_en_cours'  => ['nullable', 'string'],

            // Grossesse
            'est_enceinte'         => ['nullable', 'boolean'],
            'semaines_grossesse'   => ['nullable', 'integer', 'min:1', 'max:42'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Le patient est obligatoire.',
            'patient_id.exists'   => 'Patient introuvable.',
            'motif.required'      => 'Le motif de consultation est obligatoire.',
            'temperature.min'     => 'Température anormalement basse (min : 30°C).',
            'temperature.max'     => 'Température anormalement élevée (max : 45°C).',
            'saturation_oxygene.min' => 'Saturation O₂ invalide (min : 50%).',
            'saturation_oxygene.max' => 'Saturation O₂ ne peut dépasser 100%.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
