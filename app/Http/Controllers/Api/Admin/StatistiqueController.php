<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\RendezVous;
use App\Models\CentreSante;
use App\Models\ResultatAnalyse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    public function index()
    {
        $stats = [
            'total_utilisateurs' => User::count(),
            'total_patients' => Patient::count(),
            'total_medecins' => User::where('role', 'medecin')->count(),
            'total_consultations' => Consultation::count(),
            'total_prescriptions' => Prescription::count(),
            'total_rendez_vous' => RendezVous::count(),
            'total_resultats_analyses' => ResultatAnalyse::count(),
            'total_centres_sante' => CentreSante::count(),

            'consultations_ce_mois' => Consultation::whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
            'rdv_en_attente' => RendezVous::where('statut', 'en_attente')->count(),
            'rdv_aujourdhui' => RendezVous::whereDate('date_heure', today())->count(),

            'patients_par_mois' => Patient::select(
                DB::raw('MONTH(created_at) as mois'),
                DB::raw('COUNT(*) as total')
            )->whereYear('created_at', now()->year)->groupBy('mois')->get(),

            'consultations_par_mois' => Consultation::select(
                DB::raw('MONTH(date) as mois'),
                DB::raw('COUNT(*) as total')
            )->whereYear('date', now()->year)->groupBy('mois')->get(),

            'top_pathologies' => Consultation::select('diagnostic', DB::raw('COUNT(*) as total'))
                ->whereNotNull('diagnostic')
                ->groupBy('diagnostic')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get(),

            'repartition_par_role' => User::select('role', DB::raw('COUNT(*) as total'))
                ->groupBy('role')
                ->get(),
        ];

        return response()->json($stats);
    }

    public function parCentre(string $id)
    {
        $centre = CentreSante::with('medecins')->findOrFail($id);
        $medecinIds = $centre->medecins->pluck('id');

        $stats = [
            'centre' => $centre->nom,
            'nombre_medecins' => $centre->medecins->count(),
            'total_consultations' => Consultation::whereIn('medecin_id', $medecinIds)->count(),
            'consultations_ce_mois' => Consultation::whereIn('medecin_id', $medecinIds)
                ->whereMonth('date', now()->month)->count(),
            'total_prescriptions' => Prescription::whereIn('medecin_id', $medecinIds)->count(),
        ];

        return response()->json($stats);
    }
}