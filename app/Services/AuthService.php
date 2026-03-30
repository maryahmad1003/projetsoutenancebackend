<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use App\Models\Medecin;
use App\Models\Pharmacien;
use App\Models\Laborantin;
use App\Models\Administrateur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Enregistrement d'un nouvel utilisateur selon son rôle.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'nom'        => $data['nom'],
            'prenom'     => $data['prenom'],
            'email'      => $data['email'],
            'telephone'  => $data['telephone'] ?? null,
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            'langue'     => $data['langue'] ?? 'fr',
        ]);

        // Créer le profil spécifique au rôle
        $this->createRoleProfile($user, $data);

        $token = $user->createToken('DocSecur-Token')->accessToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Connexion d'un utilisateur.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects. Veuillez vérifier votre email et mot de passe.'],
            ]);
        }

        // Révoquer les anciens tokens
        $user->tokens()->delete();

        $token = $user->createToken('DocSecur-Token')->accessToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Déconnexion — révocation du token.
     */
    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    /**
     * Mise à jour du profil utilisateur.
     */
    public function updateProfile(User $user, array $data): User
    {
        $fillable = ['nom', 'prenom', 'telephone', 'langue', 'photo'];

        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $user->$field = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user->fresh();
    }

    /**
     * Changement de langue d'interface.
     */
    public function changeLanguage(User $user, string $langue): User
    {
        $allowed = ['fr', 'wo', 'en'];

        if (!in_array($langue, $allowed)) {
            throw new \InvalidArgumentException("Langue non supportée : {$langue}");
        }

        $user->update(['langue' => $langue]);

        return $user->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function createRoleProfile(User $user, array $data): void
    {
        switch ($user->role) {
            case 'patient':
                Patient::create([
                    'user_id'         => $user->id,
                    'num_dossier'     => $this->generateNumDossier(),
                    'date_naissance'  => $data['date_naissance'] ?? null,
                    'sexe'            => $data['sexe'] ?? null,
                    'groupe_sanguin'  => $data['groupe_sanguin'] ?? null,
                    'adresse'         => $data['adresse'] ?? null,
                    'personne_contact'=> $data['personne_contact'] ?? null,
                ]);
                break;

            case 'medecin':
                Medecin::create([
                    'user_id'          => $user->id,
                    'matricule'        => $data['matricule'] ?? $this->generateMatricule(),
                    'specialite'       => $data['specialite'] ?? 'Médecine Générale',
                    'centre_sante_id'  => $data['centre_sante_id'] ?? null,
                    'num_ordre'        => $data['num_ordre'] ?? null,
                ]);
                break;

            case 'pharmacien':
                Pharmacien::create([
                    'user_id'      => $user->id,
                    'pharmacie_id' => $data['pharmacie_id'] ?? null,
                ]);
                break;

            case 'laborantin':
                Laborantin::create([
                    'user_id'        => $user->id,
                    'laboratoire_id' => $data['laboratoire_id'] ?? null,
                    'specialite'     => $data['specialite'] ?? null,
                ]);
                break;

            case 'administrateur':
                Administrateur::create([
                    'user_id' => $user->id,
                    'niveau'  => $data['niveau'] ?? 'standard',
                ]);
                break;
        }
    }

    private function generateNumDossier(): string
    {
        return 'DS-' . date('Y') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function generateMatricule(): string
    {
        return 'MED-' . strtoupper(substr(uniqid(), -6));
    }
}
