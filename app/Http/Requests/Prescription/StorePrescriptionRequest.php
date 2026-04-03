<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'medecin';
    }

    public function rules(): array
    {
        return [
            'consultation_id' => ['required', 'exists:consultations,id'],
            'notes'           => ['nullable', 'string', 'max:2000'],

            // Tableau de médicaments
            'medicaments'                      => ['required', 'array', 'min:1'],
            'medicaments.*.medicament_id'      => ['required', 'exists:medicaments,id'],
            'medicaments.*.posologie'          => ['required', 'string', 'max:500'],
            'medicaments.*.duree_traitement'   => ['required', 'string', 'max:100'],
            'medicaments.*.quantite'           => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'consultation_id.required'         => 'La consultation associée est obligatoire.',
            'consultation_id.exists'           => 'Consultation introuvable.',
            'medicaments.required'             => 'Au moins un médicament est requis.',
            'medicaments.min'                  => 'Au moins un médicament est requis.',
            'medicaments.*.medicament_id.required' => 'L\'identifiant du médicament est requis.',
            'medicaments.*.medicament_id.exists'   => 'Médicament introuvable.',
            'medicaments.*.posologie.required'     => 'La posologie est obligatoire.',
            'medicaments.*.duree_traitement.required' => 'La durée du traitement est obligatoire.',
            'medicaments.*.quantite.required'      => 'La quantité est obligatoire.',
            'medicaments.*.quantite.min'           => 'La quantité doit être au moins 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
