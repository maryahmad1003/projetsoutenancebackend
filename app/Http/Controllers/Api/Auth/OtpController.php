<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\CarnetVaccination;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * @OA\Post(
     *     path="/api/auth/send-otp",
     *     tags={"OTP — Authentification Patient"},
     *     summary="Envoyer un code OTP au patient",
     *     description="Génère un code OTP à 6 chiffres et l'envoie par SMS au numéro fourni. TTL : 5 minutes. En mode démo, le code est retourné directement dans la réponse.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro de téléphone du patient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé au +221771234567"),
     *             @OA\Property(property="otp_demo", type="string", example="483921", description="Uniquement en mode démo (APP_ENV != production)")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Numéro de téléphone invalide"),
     *     @OA\Response(response=429, description="OTP déjà envoyé — attendez avant de réessayer")
     * )
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string|regex:/^[+0-9\s\-]{8,20}$/',
        ]);

        $telephone = trim($request->telephone);

        // Anti-spam : bloquer si un OTP est déjà actif pour ce numéro
        if ($this->otpService->exists($telephone)) {
            return response()->json([
                'message' => 'Un code OTP a déjà été envoyé à ce numéro. Veuillez patienter 5 minutes ou vérifier votre SMS.',
            ], 429);
        }

        $code = $this->otpService->generate($telephone);

        $response = [
            'message' => "Code OTP envoyé au {$telephone}",
        ];

        // En mode non-production : retourner le code en clair pour les tests / démo
        if (config('app.env') !== 'production') {
            $response['otp_demo'] = $code;
        }

        return response()->json($response);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     tags={"OTP — Authentification Patient"},
     *     summary="Vérifier le code OTP et connecter le patient",
     *     description="Vérifie le code OTP. Si valide : connecte le patient existant ou crée automatiquement un nouveau compte patient. Retourne un Bearer token Passport.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","code"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="code", type="string", example="483921", minLength=6, maxLength=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié — patient connecté",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="nouveau_compte", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Code OTP incorrect"),
     *     @OA\Response(response=403, description="Compte désactivé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'code'      => 'required|string|size:6|regex:/^\d{6}$/',
        ]);

        $telephone = trim($request->telephone);

        try {
            $this->otpService->verify($telephone, $request->code);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        // Chercher ou créer le compte patient
        $nouveauCompte = false;
        $user = User::where('telephone', $telephone)
                    ->where('role', 'patient')
                    ->first();

        if (!$user) {
            // Création automatique du compte patient
            $nouveauCompte = true;
            $user = User::create([
                'nom'       => 'Patient',
                'prenom'    => 'Nouveau',
                'email'     => 'patient_' . preg_replace('/\D/', '', $telephone) . '@docsecur.sn',
                'password'  => Hash::make(Str::random(32)),
                'telephone' => $telephone,
                'role'      => 'patient',
                'langue'    => 'fr',
                'est_actif' => true,
            ]);

            $patient = Patient::create([
                'user_id'         => $user->id,
                'num_dossier'     => 'DS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'date_naissance'  => null,
                'sexe'            => 'M',
            ]);

            DossierMedical::create([
                'patient_id'     => $patient->id,
                'numero_dossier' => 'DM-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT),
            ]);

            CarnetVaccination::create(['patient_id' => $patient->id]);
        }

        if (!$user->est_actif) {
            return response()->json([
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        $token = $user->createToken('DocSecur')->accessToken;

        $user->load(['patient', 'medecin', 'administrateur', 'pharmacien', 'laborantin']);

        return response()->json([
            'message'        => 'Connexion réussie',
            'user'           => $user,
            'token'          => $token,
            'nouveau_compte' => $nouveauCompte,
        ]);
    }
}
