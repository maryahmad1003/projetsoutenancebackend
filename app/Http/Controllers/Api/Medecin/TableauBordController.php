<?php

namespace App\Http\Controllers\Api\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DemandeAnalyse;
use App\Models\Prescription;
use App\Models\RendezVous;
use App\Models\Teleconsultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableauBordController extends Controller
{
    public function index(Request $request)
    {
        $medecin = $request->user()->medecin;

        $consultationIds = Consultation::where('medecin_id', $medecin->id)->pluck('id');
        $today = today();

        $topDiagnostics = Consultation::where('medecin_id', $medecin->id)
            ->whereNotNull('diagnostic')
            ->select('diagnostic', DB::raw('COUNT(*) as total'))
            ->groupBy('diagnostic')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $consultationsByMonth = Consultation::where('medecin_id', $medecin->id)
            ->select(DB::raw("EXTRACT(MONTH FROM date) as mois"), DB::raw('COUNT(*) as total'))
            ->whereYear('date', now()->year)
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $upcomingTeleconsultations = Teleconsultation::where('medecin_id', $medecin->id)
            ->with('patient.user')
            ->whereDate('date_debut', '>=', $today)
            ->orderBy('date_debut')
            ->limit(5)
            ->get();

        return response()->json([
            'kpis' => [
                'patients_suivis' => Consultation::where('medecin_id', $medecin->id)
                    ->join('dossiers_medicaux', 'consultations.dossier_medical_id', '=', 'dossiers_medicaux.id')
                    ->distinct('dossiers_medicaux.patient_id')
                    ->count('dossiers_medicaux.patient_id'),
                'consultations_total' => Consultation::where('medecin_id', $medecin->id)->count(),
                'consultations_ce_mois' => Consultation::where('medecin_id', $medecin->id)
                    ->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->count(),
                'prescriptions_total' => Prescription::where('medecin_id', $medecin->id)->count(),
                'analyses_en_attente' => DemandeAnalyse::where('medecin_id', $medecin->id)
                    ->whereIn('statut', ['en_attente', 'envoyee', 'en_cours'])
                    ->count(),
                'rdv_aujourdhui' => RendezVous::where('medecin_id', $medecin->id)
                    ->whereDate('date_heure', $today)
                    ->count(),
                'teleconsultations_planifiees' => Teleconsultation::where('medecin_id', $medecin->id)
                    ->whereIn('statut', ['planifiee', 'en_cours'])
                    ->count(),
                'prescriptions_envoyees_pharmacie' => Prescription::where('medecin_id', $medecin->id)
                    ->whereNotNull('pharmacie_id')
                    ->count(),
            ],
            'alertes' => [
                'rdv_en_attente_validation' => RendezVous::where('medecin_id', $medecin->id)->where('statut', 'en_attente')->count(),
                'teleconsultations_du_jour' => Teleconsultation::where('medecin_id', $medecin->id)->whereDate('date_debut', $today)->count(),
                'analyses_non_cloturees' => DemandeAnalyse::where('medecin_id', $medecin->id)->whereIn('statut', ['en_attente', 'envoyee', 'en_cours'])->count(),
            ],
            'top_pathologies' => $topDiagnostics,
            'consultations_par_mois' => $consultationsByMonth,
            'prochaines_teleconsultations' => $upcomingTeleconsultations,
            'suivi_prescriptions' => [
                'a_rediger' => max(0, Consultation::where('medecin_id', $medecin->id)->count() - Prescription::where('medecin_id', $medecin->id)->count()),
                'redigees' => Prescription::where('medecin_id', $medecin->id)->count(),
                'envoyees_pharmacie' => Prescription::where('medecin_id', $medecin->id)->whereNotNull('pharmacie_id')->count(),
            ],
            'patients_prioritaires' => Consultation::where('medecin_id', $medecin->id)
                ->with('dossierMedical.patient.user')
                ->whereIn('urgence', ['haute', 'critique'])
                ->orderByDesc('date')
                ->limit(5)
                ->get()
                ->map(fn ($consultation) => [
                    'consultation_id' => $consultation->id,
                    'date' => optional($consultation->date)->toISOString(),
                    'urgence' => $consultation->urgence,
                    'motif' => $consultation->motif,
                    'patient' => trim(($consultation->dossierMedical->patient->user->prenom ?? '') . ' ' . ($consultation->dossierMedical->patient->user->nom ?? '')),
                ]),
        ]);
    }
}
