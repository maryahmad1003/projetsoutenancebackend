<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrateur;
use App\Models\CarnetVaccination;
use App\Models\DossierMedical;
use App\Models\Laborantin;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Pharmacien;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/utilisateurs",
     *     tags={"Admin - Utilisateurs"},
     *     summary="Lister tous les utilisateurs",
     *     description="Retourne la liste paginée des utilisateurs avec filtres optionnels. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="role", in="query", description="Filtrer par rôle", required=false,
     *         @OA\Schema(type="string", enum={"medecin","patient","administrateur","pharmacien","laborantin"})
     *     ),
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom, prénom ou email", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="page", in="query", description="Numéro de page", required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Liste des utilisateurs paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['medecin', 'patient', 'administrateur', 'pharmacien', 'laborantin'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/utilisateurs",
     *     tags={"Admin - Utilisateurs"},
     *     summary="Créer un utilisateur",
     *     description="Crée un nouvel utilisateur dans le système. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","email","password","role"},
     *             @OA\Property(property="nom", type="string", example="Ndiaye"),
     *             @OA\Property(property="prenom", type="string", example="Fatou"),
     *             @OA\Property(property="email", type="string", format="email", example="fatou.ndiaye@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="role", type="string", enum={"medecin","patient","administrateur","pharmacien","laborantin"}, example="medecin"),
     *             @OA\Property(property="langue", type="string", enum={"fr","wo","en"}, example="fr")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Utilisateur créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Utilisateur créé"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $rules = [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:medecin,patient,administrateur,pharmacien,laborantin',
            'telephone' => 'nullable|string',
        ];

        if ($request->role === 'patient') {
            $rules['date_naissance'] = 'required|date';
            $rules['sexe'] = 'required|in:M,F';
            $rules['adresse'] = 'nullable|string';
            $rules['groupe_sanguin'] = 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-';
        }

        $request->validate($rules);

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

        $this->ensureRoleProfile($user, $request);

        return response()->json(['message' => 'Utilisateur créé', 'user' => $user], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/utilisateurs/{id}",
     *     tags={"Admin - Utilisateurs"},
     *     summary="Détails d'un utilisateur",
     *     description="Retourne les détails complets d'un utilisateur avec ses relations. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'utilisateur", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $user = User::with(['medecin.centreSante', 'patient.dossierMedical', 'administrateur', 'pharmacien.pharmacie', 'laborantin.laboratoire'])->findOrFail($id);
        return response()->json($user);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/utilisateurs/{id}",
     *     tags={"Admin - Utilisateurs"},
     *     summary="Modifier un utilisateur",
     *     description="Met à jour les informations d'un utilisateur. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="role", type="string", enum={"medecin","patient","administrateur","pharmacien","laborantin"}),
     *             @OA\Property(property="langue", type="string", enum={"fr","wo","en"}),
     *             @OA\Property(property="est_actif", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Utilisateur modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Utilisateur modifié"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $nextRole = $request->input('role', $user->role);

        $rules = [
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:medecin,patient,administrateur,pharmacien,laborantin',
            'langue' => 'nullable|in:fr,wo,en',
            'est_actif' => 'sometimes|boolean',
        ];

        if ($nextRole === 'patient') {
            $rules['date_naissance'] = $user->patient ? 'sometimes|date' : 'required|date';
            $rules['sexe'] = $user->patient ? 'sometimes|in:M,F' : 'required|in:M,F';
            $rules['adresse'] = 'nullable|string';
            $rules['groupe_sanguin'] = 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-';
        }

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $this->ensureRoleProfile($user, $request);

        return response()->json(['message' => 'Utilisateur modifié', 'user' => $user]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/utilisateurs/{id}",
     *     tags={"Admin - Utilisateurs"},
     *     summary="Supprimer un utilisateur",
     *     description="Supprime définitivement un utilisateur. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Utilisateur supprimé",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Utilisateur supprimé"))
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    private function ensureRoleProfile(User $user, Request $request): void
    {
        switch ($user->role) {
            case 'patient':
                $patient = $user->patient;

                if (!$patient) {
                    $patient = Patient::create([
                        'user_id' => $user->id,
                        'num_dossier' => 'DS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                        'date_naissance' => $request->date_naissance,
                        'sexe' => $request->sexe,
                        'adresse' => $request->adresse,
                        'groupe_sanguin' => $request->groupe_sanguin,
                    ]);
                } else {
                    $patient->update(array_filter([
                        'date_naissance' => $request->input('date_naissance'),
                        'sexe' => $request->input('sexe'),
                        'adresse' => $request->input('adresse'),
                        'groupe_sanguin' => $request->input('groupe_sanguin'),
                    ], fn ($value) => $value !== null));
                }

                DossierMedical::firstOrCreate(
                    ['patient_id' => $patient->id],
                    ['numero_dossier' => 'DM-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT)]
                );
                CarnetVaccination::firstOrCreate(['patient_id' => $patient->id]);
                break;

            case 'medecin':
                Medecin::firstOrCreate(['user_id' => $user->id]);
                break;

            case 'administrateur':
                Administrateur::firstOrCreate(['user_id' => $user->id]);
                break;

            case 'pharmacien':
                Pharmacien::firstOrCreate(['user_id' => $user->id]);
                break;

            case 'laborantin':
                Laborantin::firstOrCreate(['user_id' => $user->id]);
                break;
        }
    }
}
