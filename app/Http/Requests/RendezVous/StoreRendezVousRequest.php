<?php

namespace App\Http\Requests\RendezVous;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRendezVousRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['patient', 'medecin']);
    }

    public function rules(): array
    {
        return [
            'medecin_id'  => ['required', 'exists:medecins,id'],
            'date_heure'  => ['required', 'date', 'after:now'],
            'duree'       => ['nullable', 'integer', 'min:10', 'max:240'],
            'motif'       => ['required', 'string', 'max:500'],
            'type'        => ['nullable', 'in:consultation,suivi,urgence,teleconsultation'],
        ];
    }

    public function messages(): array
    {
        return [
            'medecin_id.required'  => 'Le médecin est obligatoire.',
            'medecin_id.exists'    => 'Médecin introuvable.',
            'date_heure.required'  => 'La date et l\'heure du rendez-vous sont obligatoires.',
            'date_heure.after'     => 'Le rendez-vous doit être dans le futur.',
            'motif.required'       => 'Le motif du rendez-vous est obligatoire.',
            'duree.min'            => 'La durée minimale est de 10 minutes.',
            'duree.max'            => 'La durée maximale est de 4 heures.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
