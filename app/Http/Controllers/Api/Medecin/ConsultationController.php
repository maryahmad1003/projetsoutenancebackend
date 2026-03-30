<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\Notification;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function getPatients(Request $request)
    {
        $medecin = $request->user()->medecin;

        if (!$medecin) {
            return response()->json(['message' => 'Profil médecin introuvable'], 404);
        }

        $patientIds = Consultation::where('medecin_id', $medecin->id)
            ->join('dossiers_medicaux', 'consultations.dossier_medical_id', '=', 'dossiers_medicaux.id')
            ->pluck('dossiers_medicaux.patient_id')
            ->unique();

        $query = Patient::with('user')->whereIn('id', $patientIds);

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

    public function getPatient(Request $request, string $id)
    {
        $patient = Patient::with([
            'user',
            'dossierMedical.consultations.medecin.user',
            'dossierMedical.consultations.prescriptions.medicaments',
        ])->findOrFail($id);

        return response()->json($patient);
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'dossier_medical_id' => 'required|exists:dossiers_medicaux,id',
            'motif' => 'required|string',
        ]);

        $medecin = $request->user()->medecin;

        // Calculer l'IMC si taille et poids fournis
        $imc = null;
        if ($request->poids && $request->taille) {
            $tailleM = $request->taille / 100;
            $imc = round($request->poids / ($tailleM * $tailleM), 1);
        }

        $consultation = Consultation::create([
            'dossier_medical_id' => $request->dossier_medical_id,
            'medecin_id' => $medecin->id,
            'date' => now(),
            'motif' => $request->motif,
            'diagnostic' => $request->diagnostic,
            'notes' => $request->notes,
            // Constantes vitales
            'tension' => $request->tension,
            'poids' => $request->poids,
            'temperature' => $request->temperature,
            'frequence_cardiaque' => $request->frequence_cardiaque,
            'glycemie' => $request->glycemie,
            'taille' => $request->taille,
            'imc' => $imc,
            'saturation_oxygene' => $request->saturation_oxygene,
            'frequence_respiratoire' => $request->frequence_respiratoire,
            // Examen clinique
            'examen_clinique' => $request->examen_clinique,
            'antecedents_signales' => $request->antecedents_signales,
            'allergies_signalees' => $request->allergies_signalees,
            'traitement_en_cours' => $request->traitement_en_cours,
            // Grossesse
            'est_enceinte' => $request->est_enceinte ?? false,
            'semaines_grossesse' => $request->semaines_grossesse,
            'date_derniere_regle' => $request->date_derniere_regle,
            'date_accouchement_prevue' => $request->date_accouchement_prevue,
            'groupe_sanguin_grossesse' => $request->groupe_sanguin_grossesse,
            'observations_grossesse' => $request->observations_grossesse,
            // Suivi
            'recommandations' => $request->recommandations,
            'prochain_rdv' => $request->prochain_rdv,
            'urgence' => $request->urgence ?? 'faible',
            'type_consultation' => $request->type_consultation ?? 'presentiel',
        ]);

        // Créer automatiquement le prochain RDV si date fournie
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

        // Notifier le patient
        $patient = $consultation->dossierMedical->patient;
        Notification::create([
            'user_id' => $patient->user_id,
            'type' => 'suivi',
            'message' => 'Votre consultation du ' . now()->format('d/m/Y') . ' a été enregistrée par Dr. ' . $request->user()->nom,
            'canal' => 'sms',
            'date_envoi' => now(),
        ]);

        return response()->json([
            'message' => 'Consultation enregistrée avec succès',
            'consultation' => $consultation->load('dossierMedical.patient.user')
        ], 201);
    }

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

    public function update(Request $request, string $id)
    {
        $consultation = Consultation::findOrFail($id);
        
        // Recalculer l'IMC si modifié
        $data = $request->all();
        if (isset($data['poids']) && isset($data['taille'])) {
            $tailleM = $data['taille'] / 100;
            $data['imc'] = round($data['poids'] / ($tailleM * $tailleM), 1);
        }

        $consultation->update($data);
        return response()->json(['message' => 'Consultation mise à jour', 'consultation' => $consultation]);
    }

    public function destroy(string $id)
    {
        Consultation::findOrFail($id)->delete();
        return response()->json(['message' => 'Consultation supprimée']);
    }
}