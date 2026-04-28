<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\CarnetVaccination;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(private QRCodeService $qrCodeService) {}

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentification"},
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Crée un compte utilisateur avec le rôle spécifié et génère un token d'accès.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","email","password","password_confirmation","role"},
     *             @OA\Property(property="nom", type="string", example="Diallo"),
     *             @OA\Property(property="prenom", type="string", example="Amadou"),
     *             @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="role", type="string", enum={"patient"}, example="patient"),
     *             @OA\Property(property="langue", type="string", enum={"fr","wo","en"}, example="fr"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-05-15", description="Requis si rôle = patient"),
     *             @OA\Property(property="sexe", type="string", enum={"M","F"}, example="M", description="Requis si rôle = patient"),
     *             @OA\Property(property="adresse", type="string", example="Dakar, Plateau"),
     *             @OA\Property(property="groupe_sanguin", type="string", example="O+")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Inscription réussie"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string',
            'role' => 'required|in:patient',
            'langue' => 'nullable|in:fr,wo,en',
            'date_naissance' => 'required_if:role,patient|nullable|date',
            'sexe' => 'required_if:role,patient|nullable|in:M,F',
            'adresse' => 'nullable|string|max:255',
            'groupe_sanguin' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = DB::transaction(function () use ($request) {
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

            $patientQrCode = null;
            $patientMeta = null;

            $patient = Patient::create([
                'user_id'         => $user->id,
                'num_dossier'     => 'DS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'date_naissance'  => $request->date_naissance,
                'sexe'            => $request->sexe ?? 'M',
                'adresse'         => $request->adresse,
                'groupe_sanguin'  => $request->groupe_sanguin,
            ]);

            $dossier = DossierMedical::create([
                'patient_id'     => $patient->id,
                'numero_dossier' => 'DM-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT),
            ]);

            CarnetVaccination::create(['patient_id' => $patient->id]);

            $qrCode = $this->qrCodeService->genererQRCode($patient);
            $patientQrCode = [
                'qr_code' => base64_encode($qrCode['svg']),
                'payload' => $qrCode['payload'],
                'expires_at' => $qrCode['expires'],
            ];
            $patientMeta = [
                'id' => $patient->id,
                'num_dossier' => $patient->num_dossier,
                'numero_dossier_medical' => $dossier->numero_dossier,
            ];

            return [
                'user' => $user->load(['patient']),
                'patient_qr_code' => $patientQrCode,
                'patient_meta' => $patientMeta,
            ];
        });

        $token = $payload['user']->createToken('DocSecur')->accessToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $payload['user'],
            'token' => $token,
            'patient' => $payload['patient_meta'],
            'patient_qr_code' => $payload['patient_qr_code'],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentifie l'utilisateur et retourne un Bearer token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Identifiants incorrects"),
     *     @OA\Response(response=403, description="Compte désactivé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentification"},
     *     summary="Déconnexion",
     *     description="Révoque le token de l'utilisateur connecté.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Déconnexion réussie",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Déconnexion réussie"))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/profil",
     *     tags={"Authentification"},
     *     summary="Profil de l'utilisateur connecté",
     *     description="Retourne les informations complètes du profil avec les relations selon le rôle.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Profil retourné", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function profil(Request $request)
    {
        $user = $request->user();
        $user->load(['medecin', 'patient', 'administrateur', 'pharmacien', 'laborantin']);

        return response()->json($user);
    }

    /**
     * @OA\Put(
     *     path="/api/profil",
     *     tags={"Authentification"},
     *     summary="Modifier le profil",
     *     description="Met à jour les informations du profil de l'utilisateur connecté. Supporte l'upload de photo (multipart/form-data).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="nom", type="string", example="Diallo"),
     *                 @OA\Property(property="prenom", type="string", example="Amadou"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="langue", type="string", enum={"fr","wo","en"}, example="fr"),
     *                 @OA\Property(property="photo_profil", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profil mis à jour"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/changer-langue",
     *     tags={"Authentification"},
     *     summary="Changer la langue de l'interface",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"langue"},
     *             @OA\Property(property="langue", type="string", enum={"fr","wo","en"}, example="fr")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Langue changée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Langue changée en fr"))
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function changerLangue(Request $request)
    {
        $request->validate(['langue' => 'required|in:fr,wo,en']);

        $request->user()->update(['langue' => $request->langue]);

        return response()->json([
            'message' => 'Langue changée en ' . $request->langue
        ]);
    }
}
