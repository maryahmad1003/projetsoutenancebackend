<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\CarnetVaccination;
use App\Models\Medecin;
use App\Models\Administrateur;
use App\Models\Pharmacien;
use App\Models\Laborantin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ====== INSCRIPTION ======
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string',
            'role' => 'required|in:medecin,patient,administrateur,pharmacien,laborantin',
            'langue' => 'nullable|in:fr,wo,en',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'role' => $request->role,
            'langue' => $request->langue ?? 'fr',
            'est_actif' => true,
        ]);

        // Créer le profil selon le rôle
        switch ($user->role) {
            case 'patient':
                $patient = Patient::create([
                    'user_id'         => $user->id,
                    'num_dossier'     => 'DS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'date_naissance'  => $request->date_naissance,
                    'sexe'            => $request->sexe ?? 'M',
                    'adresse'         => $request->adresse,
                    'groupe_sanguin'  => $request->groupe_sanguin,
                ]);
                DossierMedical::create([
                    'patient_id'     => $patient->id,
                    'numero_dossier' => 'DM-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT),
                ]);
                CarnetVaccination::create(['patient_id' => $patient->id]);
                break;

            case 'medecin':
                Medecin::create(['user_id' => $user->id]);
                break;

            case 'administrateur':
                Administrateur::create(['user_id' => $user->id]);
                break;

            case 'pharmacien':
                Pharmacien::create(['user_id' => $user->id]);
                break;

            case 'laborantin':
                Laborantin::create(['user_id' => $user->id]);
                break;
        }

        $token = $user->createToken('DocSecur')->accessToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // ====== CONNEXION ======
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects'
            ], 401);
        }

        if (!$user->est_actif) {
            return response()->json([
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        $token = $user->createToken('DocSecur')->accessToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // ====== DÉCONNEXION ======
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    // ====== PROFIL ======
    public function profil(Request $request)
    {
        $user = $request->user();
        $user->load(['medecin', 'patient', 'administrateur', 'pharmacien', 'laborantin']);

        return response()->json($user);
    }

    // ====== MODIFIER PROFIL ======
    public function updateProfil(Request $request)
    {
        $request->validate([
            'nom'          => 'sometimes|string|max:255',
            'prenom'       => 'sometimes|string|max:255',
            'telephone'    => 'sometimes|string|regex:/^[+0-9\s]{8,15}$/',
            'langue'       => 'sometimes|in:fr,wo,en',
            'photo_profil' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();
        $data = $request->only(['nom', 'prenom', 'telephone', 'langue']);

        if ($request->hasFile('photo_profil')) {
            $path = $request->file('photo_profil')->store('photos_profil', 'public');
            $data['photo_profil'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil mis à jour',
            'user' => $user
        ]);
    }

    // ====== CHANGER LANGUE ======
    public function changerLangue(Request $request)
    {
        $request->validate(['langue' => 'required|in:fr,wo,en']);

        $request->user()->update(['langue' => $request->langue]);

        return response()->json([
            'message' => 'Langue changée en ' . $request->langue
        ]);
    }
}