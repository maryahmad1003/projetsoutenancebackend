<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\CarnetVaccination;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DossierMedicalController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::with(['user', 'dossierMedical'])->paginate(20);
        return response()->json($patients);
    }

    public function show(string $id)
    {
        $patient = Patient::with([
            'user',
            'dossierMedical.consultations.medecin.user',
            'dossierMedical.consultations.prescriptions.medicaments',
            'dossierMedical.resultatsAnalyses',
            'carnetVaccination.vaccins',
            'rendezVous.medecin.user',
        ])->findOrFail($id);

        return response()->json($patient);
    }

    /**
     * @OA\Get(
     *     path="/api/patient/dossier",
     *     tags={"Patient - Dossier"},
     *     summary="Dossier médical du patient connecté",
     *     description="Retourne le dossier médical complet du patient authentifié (consultations, prescriptions, résultats d'analyses).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Dossier médical", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function monDossier(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = DossierMedical::where('patient_id', $patient->id)
            ->with(['consultations.medecin.user', 'consultations.prescriptions.medicaments', 'resultatsAnalyses'])
            ->first();

        return response()->json($dossier);
    }

    /**
     * @OA\Get(
     *     path="/api/patient/historique",
     *     tags={"Patient - Dossier"},
     *     summary="Historique des consultations du patient connecté",
     *     description="Retourne toutes les consultations du patient authentifié, triées par date décroissante.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Historique des consultations", @OA\JsonContent(type="array", @OA\Items(type="object"))),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function monHistorique(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = $patient->dossierMedical;

        $consultations = $dossier->consultations()
            ->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($consultations);
    }

    public function historique(string $id)
    {
        $patient = Patient::findOrFail($id);
        $dossier = $patient->dossierMedical;

        $consultations = $dossier->consultations()
            ->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($consultations);
    }

    public function update(Request $request, string $id)
    {
        $dossier = DossierMedical::findOrFail($id);
        $dossier->update($request->only(['antecedents', 'allergies', 'notes_generales']));

        return response()->json(['message' => 'Dossier mis à jour', 'dossier' => $dossier]);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/patients",
     *     tags={"Médecin - Patients"},
     *     summary="Créer un nouveau patient",
     *     description="Crée un compte patient avec un mot de passe temporaire généré automatiquement. Le médecin reçoit le mot de passe temporaire dans la réponse.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","email","date_naissance","sexe"},
     *             @OA\Property(property="nom", type="string", example="Sow"),
     *             @OA\Property(property="prenom", type="string", example="Mariama"),
     *             @OA\Property(property="email", type="string", format="email", example="mariama.sow@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1985-03-20"),
     *             @OA\Property(property="sexe", type="string", enum={"M","F"}, example="F"),
     *             @OA\Property(property="adresse", type="string", example="Thiès, Médina"),
     *             @OA\Property(property="groupe_sanguin", type="string", example="A+")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Patient créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Patient créé avec succès"),
     *             @OA\Property(property="patient", type="object"),
     *             @OA\Property(property="mot_de_passe", type="string", example="xK9mP2qR4t", description="Mot de passe temporaire à communiquer au patient")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function creerPatient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'            => 'required|string|max:255',
            'prenom'         => 'required|string|max:255',
            'email'          => 'required|email|unique:users',
            'telephone'      => 'nullable|string',
            'date_naissance' => 'required|date',
            'sexe'           => 'required|in:M,F',
            'adresse'        => 'nullable|string',
            'groupe_sanguin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $motDePasse = Str::random(10);

        $user = User::create([
            'nom'       => $request->nom,
            'prenom'    => $request->prenom,
            'email'     => $request->email,
            'password'  => Hash::make($motDePasse),
            'telephone' => $request->telephone,
            'role'      => 'patient',
            'langue'    => 'fr',
            'est_actif' => true,
        ]);

        $patient = Patient::create([
            'user_id'        => $user->id,
            'num_dossier'    => 'DS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            'date_naissance' => $request->date_naissance,
            'sexe'           => $request->sexe,
            'adresse'        => $request->adresse,
            'groupe_sanguin' => $request->groupe_sanguin,
        ]);

        DossierMedical::create([
            'patient_id'     => $patient->id,
            'numero_dossier' => 'DM-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT),
        ]);

        CarnetVaccination::create(['patient_id' => $patient->id]);

        return response()->json([
            'message'      => 'Patient créé avec succès',
            'patient'      => $patient->load('user'),
            'mot_de_passe' => $motDePasse,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/medecin/patients/{id}/update",
     *     tags={"Médecin - Patients"},
     *     summary="Mettre à jour les informations d'un patient",
     *     description="Met à jour les informations médicales et personnelles d'un patient.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID du patient",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="groupe_sanguin", type="string"),
     *             @OA\Property(property="personne_contact", type="string"),
     *             @OA\Property(property="tel_contact", type="string"),
     *             @OA\Property(property="taille", type="number"),
     *             @OA\Property(property="poids", type="number"),
     *             @OA\Property(property="profession", type="string"),
     *             @OA\Property(property="situation_matrimoniale", type="string"),
     *             @OA\Property(property="nombre_enfants", type="integer"),
     *             @OA\Property(property="antecedents_medicaux", type="string"),
     *             @OA\Property(property="antecedents_chirurgicaux", type="string"),
     *             @OA\Property(property="antecedents_familiaux", type="string"),
     *             @OA\Property(property="allergies", type="string"),
     *             @OA\Property(property="traitement_en_cours", type="string"),
     *             @OA\Property(property="assurance", type="string"),
     *             @OA\Property(property="numero_assurance", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Patient mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Informations patient mises à jour"),
     *             @OA\Property(property="patient", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Patient non trouvé")
     * )
     */
    public function updatePatient(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $patient->update($request->only([
            'adresse', 'groupe_sanguin', 'personne_contact', 'tel_contact',
            'taille', 'poids', 'profession', 'situation_matrimoniale', 'nombre_enfants',
            'antecedents_medicaux', 'antecedents_chirurgicaux', 'antecedents_familiaux',
            'allergies', 'traitement_en_cours', 'assurance', 'numero_assurance'
        ]));

        if ($request->has('antecedents') || $request->has('allergies_dossier') || $request->has('notes_generales')) {
            $patient->dossierMedical->update([
                'antecedents' => $request->antecedents,
                'allergies' => $request->allergies_dossier,
                'notes_generales' => $request->notes_generales,
            ]);
        }

        return response()->json([
            'message' => 'Informations patient mises à jour',
            'patient' => $patient->load(['user', 'dossierMedical'])
        ]);
    }
}
