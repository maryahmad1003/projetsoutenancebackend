<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un médecin peut créer un patient depuis l'interface médecin
        return $this->user()?->role === 'medecin';
    }

    public function rules(): array
    {
        return [
            'nom'                    => ['required', 'string', 'max:255'],
            'prenom'                 => ['required', 'string', 'max:255'],
            'telephone'              => ['required', 'string', 'regex:/^[+0-9\s\-]{8,20}$/'],
            'email'                  => ['nullable', 'email', 'unique:users,email'],
            'date_naissance'         => ['nullable', 'date', 'before:today'],
            'sexe'                   => ['required', 'in:M,F'],
            'adresse'                => ['nullable', 'string', 'max:500'],
            'groupe_sanguin'         => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'taille'                 => ['nullable', 'numeric', 'min:20', 'max:300'],
            'poids'                  => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'profession'             => ['nullable', 'string', 'max:255'],
            'situation_matrimoniale' => ['nullable', 'in:celibataire,marie,divorce,veuf'],
            'nombre_enfants'         => ['nullable', 'integer', 'min:0'],
            'antecedents_medicaux'   => ['nullable', 'string'],
            'antecedents_chirurgicaux'=> ['nullable', 'string'],
            'antecedents_familiaux'  => ['nullable', 'string'],
            'allergies'              => ['nullable', 'string'],
            'traitement_en_cours'    => ['nullable', 'string'],
            'assurance'              => ['nullable', 'string', 'max:255'],
            'numero_assurance'       => ['nullable', 'string', 'max:100'],
            'personne_contact'       => ['nullable', 'string', 'max:255'],
            'tel_contact'            => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'       => 'Le nom du patient est obligatoire.',
            'prenom.required'    => 'Le prénom du patient est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'sexe.required'      => 'Le sexe est obligatoire.',
            'sexe.in'            => 'Le sexe doit être M (masculin) ou F (féminin).',
            'email.unique'       => 'Cette adresse email est déjà utilisée.',
            'groupe_sanguin.in'  => 'Groupe sanguin invalide. Valeurs : A+, A-, B+, B-, AB+, AB-, O+, O-',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
