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

    public function monDossier(Request $request)
    {
        $patient = $request->user()->patient;
        $dossier = DossierMedical::where('patient_id', $patient->id)
            ->with(['consultations.medecin.user', 'consultations.prescriptions.medicaments', 'resultatsAnalyses'])
            ->first();

        return response()->json($dossier);
    }

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

        // Mot de passe temporaire généré automatiquement
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

    public function updatePatient(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);
        
        $patient->update($request->only([
            'adresse', 'groupe_sanguin', 'personne_contact', 'tel_contact',
            'taille', 'poids', 'profession', 'situation_matrimoniale', 'nombre_enfants',
            'antecedents_medicaux', 'antecedents_chirurgicaux', 'antecedents_familiaux',
            'allergies', 'traitement_en_cours', 'assurance', 'numero_assurance'
        ]));

        // Mettre à jour le dossier médical aussi
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