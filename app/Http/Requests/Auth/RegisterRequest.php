<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nom'       => ['required', 'string', 'max:255'],
            'prenom'    => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'telephone' => ['nullable', 'string', 'regex:/^[+0-9\s\-]{8,20}$/'],
            'role'      => ['required', 'in:medecin,patient,administrateur,pharmacien,laborantin'],
            'langue'    => ['nullable', 'in:fr,wo,en'],
        ];

        // Champs supplémentaires obligatoires pour les patients
        if ($this->input('role') === 'patient') {
            $rules['date_naissance'] = ['nullable', 'date', 'before:today'];
            $rules['sexe']           = ['nullable', 'in:M,F'];
            $rules['adresse']        = ['nullable', 'string', 'max:500'];
            $rules['groupe_sanguin'] = ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required'      => 'Le nom est obligatoire.',
            'prenom.required'   => 'Le prénom est obligatoire.',
            'email.required'    => 'L\'adresse email est obligatoire.',
            'email.unique'      => 'Cette adresse email est déjà utilisée.',
            'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'=> 'La confirmation du mot de passe ne correspond pas.',
            'role.in'           => 'Rôle invalide. Valeurs acceptées : medecin, patient, administrateur, pharmacien, laborantin.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
