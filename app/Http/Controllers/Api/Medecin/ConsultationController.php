<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DossierMedical;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\Notification;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/medecin/patients",
     *     tags={"Médecin - Patients"},
     *     summary="Lister les patients",
     *     description="Retourne la liste paginée de tous les patients disponibles dans la base.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom, prénom, téléphone ou numéro de dossier",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="sexe", in="query", description="Filtrer par sexe",
     *         @OA\Schema(type="string", enum={"M","F"})
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Liste des patients paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Profil médecin introuvable"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function getPatients(Request $request)
    {
        $medecin = $request->user()->medecin;

        if (!$medecin) {
            return response()->json(['message' => 'Profil médecin introuvable'], 404);
        }

        $query = Patient::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            })->orWhere('num_dossier', 'like', "%{$search}%");
        }

        if ($request->filled('sexe')) {
            $query->where('sexe', $request->sexe);
        }

        $patients = $query->paginate($request->get('per_page', 15));

        $patients->getCollection()->transform(function ($p) {
            return [
                'id'             => $p->id,
                'ref'            => $p->num_dossier,
                'nom'            => $p->user->nom,
                'prenom'         => $p->user->prenom,
                'telephone'      => $p->user->telephone,
                'date_naissance' => $p->date_naissance,
                'sexe'           => $p->sexe,
                'groupe_sanguin' => $p->groupe_sanguin,
                'adresse'        => $p->adresse,
                'created_at'     => $p->created_at,
            ];
        });

        return response()->json($patients);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/patients/{id}",
     *     tags={"Médecin - Patients"},
     *     summary="Détails d'un patient",
     *     description="Retourne les informations complètes d'un patient avec son dossier médical et ses consultations.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID du patient",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails du patient", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Patient non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function getPatient(Request $request, string $id)
    {
        $patient = Patient::with([
            'user',
            'dossierMedical.consultations.medecin.user',
            'dossierMedical.consultations.prescriptions.medicaments',
        ])->findOrFail($id);

        return response()->json($patient);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/patients/{id}/historique",
     *     tags={"Médecin - Patients"},
     *     summary="Historique des consultations d'un patient",
     *     description="Retourne les consultations d'un patient avec filtres de date.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID du patient", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date_debut", in="query", description="Date de début (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_fin", in="query", description="Date de fin (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Historique paginé", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Patient non trouvé")
     * )
     */
    public function getHistorique(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);
        $dossier = $patient->dossierMedical;

        $query = $dossier->consultations()
            ->with(['medecin.user', 'prescriptions.medicaments'])
            ->orderBy('date', 'desc');

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        return response()->json($query->paginate($request->get('per_page', 10)));
    }

    public function getConstantesVitales(Request $request, string $id)
    {
        $patient = Patient::with('dossierMedical.consultations')->findOrFail($id);
        $dossier = $patient->dossierMedical;

        if (!$dossier) {
            return response()->json([]);
        }

        $constantes = $dossier->consultations()
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($consultation) {
                [$tensionSystolique, $tensionDiastolique] = array_pad(
                    explode('/', (string) $consultation->tension),
                    2,
                    null
                );

                return [
                    'id' => $consultation->id,
                    'date' => optional($consultation->date)->format('Y-m-d'),
                    'tensionSystolique' => $tensionSystolique !== null ? (float) $tensionSystolique : null,
                    'tensionDiastolique' => $tensionDiastolique !== null ? (float) $tensionDiastolique : null,
                    'frequenceCardiaque' => $consultation->frequence_cardiaque,
                    'temperature' => $consultation->temperature,
                    'poids' => $consultation->poids,
                    'saturationO2' => $consultation->saturation_oxygene,
                    'glycemie' => $consultation->glycemie,
                ];
            })
            ->values();

        return response()->json($constantes);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/consultations",
     *     tags={"Médecin - Consultations"},
     *     summary="Lister les consultations du médecin",
     *     description="Retourne la liste paginée des consultations du médecin connecté avec filtres.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="date", in="query", description="Filtrer par date (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="urgence", in="query", description="Filtrer par niveau d'urgence",
     *         @OA\Schema(type="string", enum={"faible","modere","eleve"})
     *     ),
     *     @OA\Parameter(name="type_consultation", in="query", description="Type de consultation",
     *         @OA\Schema(type="string", enum={"presentiel","teleconsultation"})
     *     ),
     *     @OA\Parameter(name="search", in="query", description="Recherche par nom du patient, motif ou diagnostic",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Liste des consultations paginée", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;

        $query = Consultation::where('medecin_id', $medecin->id)
            ->with(['dossierMedical.patient.user', 'prescriptions.medicaments']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('urgence')) {
            $query->where('urgence', $request->urgence);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('dossierMedical.patient.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            })->orWhere('motif', 'like', "%{$search}%")
              ->orWhere('diagnostic', 'like', "%{$search}%");
        }
        if ($request->filled('type_consultation')) {
            $query->where('type_consultation', $request->type_consultation);
        }

        $consultations = $query->orderBy('date', 'desc')->paginate($request->get('per_page', 20));

        return response()->json($consultations);
    }

    /**
     * @OA\Post(
     *     path="/api/medecin/consultations",
     *     tags={"Médecin - Consultations"},
     *     summary="Créer une consultation",
     *     description="Enregistre une nouvelle consultation avec les constantes vitales et données médicales. Crée automatiquement un prochain RDV si la date est fournie.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dossier_medical_id","motif"},
     *             @OA\Property(property="dossier_medical_id", type="integer", example=1),
     *             @OA\Property(property="motif", type="string", example="Fièvre persistante"),
     *             @OA\Property(property="diagnostic", type="string", example="Paludisme"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="tension", type="string", example="120/80"),
     *             @OA\Property(property="poids", type="number", format="float", example=70.5),
     *             @OA\Property(property="temperature", type="number", format="float", example=38.5),
     *             @OA\Property(property="frequence_cardiaque", type="integer", example=78),
     *             @OA\Property(property="glycemie", type="number", format="float"),
     *             @OA\Property(property="taille", type="number", format="float", example=175, description="En cm - l'IMC sera calculé automatiquement"),
     *             @OA\Property(property="saturation_oxygene", type="number", format="float", example=98),
     *             @OA\Property(property="frequence_respiratoire", type="integer"),
     *             @OA\Property(property="examen_clinique", type="string"),
     *             @OA\Property(property="antecedents_signales", type="string"),
     *             @OA\Property(property="allergies_signalees", type="string"),
     *             @OA\Property(property="traitement_en_cours", type="string"),
     *             @OA\Property(property="est_enceinte", type="boolean", default=false),
     *             @OA\Property(property="semaines_grossesse", type="integer"),
     *             @OA\Property(property="recommandations", type="string"),
     *             @OA\Property(property="prochain_rdv", type="string", format="date", example="2026-04-15"),
     *             @OA\Property(property="urgence", type="string", enum={"faible","modere","eleve"}, default="faible"),
     *             @OA\Property(property="type_consultation", type="string", enum={"presentiel","teleconsultation"}, default="presentiel")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Consultation enregistrée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Consultation enregistrée avec succès"),
     *             @OA\Property(property="consultation", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'dossier_medical_id'  => 'nullable|required_without:patient_id|exists:dossiers_medicaux,id',
            'patient_id'          => 'nullable|required_without:dossier_medical_id|exists:patients,id',
            'date_consultation'   => 'required|date',
            'motif'               => 'required|string',
            'type_consultation'   => 'nullable|in:presentiel,teleconsultation',
            'urgence'             => 'nullable|in:faible,moyenne,haute,critique',
            'poids'               => 'nullable|numeric|min:1|max:500',
            'taille'              => 'nullable|numeric|min:30|max:250',
            'temperature'         => 'nullable|numeric|min:30|max:45',
            'frequence_cardiaque' => 'nullable|integer|min:20|max:300',
            'saturation_oxygene'  => 'nullable|numeric|min:50|max:100',
            'prochain_rdv'        => 'nullable|date|after:today',
        ]);

        $medecin = $request->user()->medecin;
        $dossierMedicalId = $request->dossier_medical_id;

        if (!$dossierMedicalId && $request->patient_id) {
            $dossierMedicalId = DossierMedical::where('patient_id', $request->patient_id)->value('id');
        }

        $imc = null;
        if ($request->poids && $request->taille) {
            $tailleM = $request->taille / 100;
            $imc = round($request->poids / ($tailleM * $tailleM), 1);
        }

        $consultation = Consultation::create([
            'dossier_medical_id' => $dossierMedicalId,
            'medecin_id' => $medecin->id,
            'date' => $request->date_consultation,
            'motif' => $request->motif,
            'diagnostic' => $request->diagnostic,
            'notes' => $request->notes,
            'tension' => $request->tension,
            'poids' => $request->poids,
            'temperature' => $request->temperature,
            'frequence_cardiaque' => $request->frequence_cardiaque,
            'glycemie' => $request->glycemie,
            'taille' => $request->taille,
            'imc' => $imc,
            'saturation_oxygene' => $request->saturation_oxygene,
            'frequence_respiratoire' => $request->frequence_respiratoire,
            'examen_clinique' => $request->examen_clinique,
            'antecedents_signales' => $request->antecedents_signales,
            'allergies_signalees' => $request->allergies_signalees,
            'traitement_en_cours' => $request->traitement_en_cours,
            'est_enceinte' => $request->est_enceinte ?? false,
            'semaines_grossesse' => $request->semaines_grossesse,
            'date_derniere_regle' => $request->date_derniere_regle,
            'date_accouchement_prevue' => $request->date_accouchement_prevue,
            'groupe_sanguin_grossesse' => $request->groupe_sanguin_grossesse,
            'observations_grossesse' => $request->observations_grossesse,
            'recommandations' => $request->recommandations,
            'prochain_rdv' => $request->prochain_rdv,
            'urgence' => $request->urgence ?? 'faible',
            'type_consultation' => $request->type_consultation ?? 'presentiel',
        ]);

        if ($request->prochain_rdv) {
            $dossier = $consultation->dossierMedical;
            RendezVous::create([
                'patient_id' => $dossier->patient_id,
                'medecin_id' => $medecin->id,
                'date_heure' => $request->prochain_rdv . ' 09:00:00',
                'motif' => 'Suivi - ' . $request->motif,
                'statut' => 'confirme',
                'type' => 'suivi',
            ]);
        }

        $patient = $consultation->dossierMedical->patient;
        Notification::create([
            'user_id' => $patient->user_id,
            'type' => 'suivi',
            'message' => 'Votre consultation du ' . \Carbon\Carbon::parse($request->date_consultation)->format('d/m/Y') . ' a été enregistrée par Dr. ' . $request->user()->nom,
            'canal' => 'sms',
            'date_envoi' => now(),
        ]);

        return response()->json([
            'message' => 'Consultation enregistrée avec succès',
            'consultation' => $consultation->load('dossierMedical.patient.user')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/consultations/{id}",
     *     tags={"Médecin - Consultations"},
     *     summary="Détails d'une consultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la consultation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=404, description="Consultation non trouvée")
     * )
     */
    public function show(string $id)
    {
        $consultation = Consultation::with([
            'dossierMedical.patient.user',
            'medecin.user',
            'prescriptions.medicaments',
            'teleconsultation'
        ])->findOrFail($id);

        return response()->json($consultation);
    }

    /**
     * @OA\Put(
     *     path="/api/medecin/consultations/{id}",
     *     tags={"Médecin - Consultations"},
     *     summary="Modifier une consultation",
     *     description="Met à jour les données d'une consultation. L'IMC est recalculé si poids et taille sont fournis.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="motif", type="string"),
     *             @OA\Property(property="diagnostic", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="tension", type="string"),
     *             @OA\Property(property="poids", type="number"),
     *             @OA\Property(property="temperature", type="number"),
     *             @OA\Property(property="recommandations", type="string"),
     *             @OA\Property(property="urgence", type="string", enum={"faible","modere","eleve"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Consultation mise à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Consultation mise à jour"),
     *             @OA\Property(property="consultation", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Consultation non trouvée")
     * )
     */
    public function update(Request $request, string $id)
    {
        $consultation = Consultation::findOrFail($id);

        $data = $request->all();
        if (isset($data['poids']) && isset($data['taille'])) {
            $tailleM = $data['taille'] / 100;
            $data['imc'] = round($data['poids'] / ($tailleM * $tailleM), 1);
        }

        $consultation->update($data);
        return response()->json(['message' => 'Consultation mise à jour', 'consultation' => $consultation]);
    }

    /**
     * @OA\Delete(
     *     path="/api/medecin/consultations/{id}",
     *     tags={"Médecin - Consultations"},
     *     summary="Supprimer une consultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Consultation supprimée",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Consultation supprimée"))
     *     ),
     *     @OA\Response(response=404, description="Consultation non trouvée")
     * )
     */
    public function destroy(string $id)
    {
        Consultation::findOrFail($id)->delete();
        return response()->json(['message' => 'Consultation supprimée']);
    }

    /**
     * @OA\Get(
     *     path="/api/medecin/rendez-vous",
     *     tags={"Médecin - Rendez-vous"},
     *     summary="Lister les rendez-vous du médecin",
     *     description="Retourne les rendez-vous du médecin connecté. Filtre par date possible.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="date", in="query", description="Filtrer par date (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Liste des rendez-vous", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function getRendezVous(Request $request)
    {
        $medecin = $request->user()->medecin;

        if (!$medecin) {
            return response()->json(['message' => 'Profil médecin introuvable'], 404);
        }

        $query = RendezVous::where('medecin_id', $medecin->id)
            ->with(['patient.user']);

        if ($request->filled('date')) {
            $query->whereDate('date_heure', $request->date);
        }

        $rdvs = $query->orderBy('date_heure', 'asc')->get();

        return response()->json($rdvs);
    }
}
