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
    /**
     * @OA\Get(
     *     path="/api/admin/statistiques",
     *     tags={"Admin - Statistiques"},
     *     summary="Statistiques globales du système",
     *     description="Retourne les statistiques globales : totaux, tendances mensuelles, top pathologies, répartition par rôle. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques globales",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_utilisateurs", type="integer", example=150),
     *             @OA\Property(property="total_patients", type="integer", example=80),
     *             @OA\Property(property="total_medecins", type="integer", example=20),
     *             @OA\Property(property="total_consultations", type="integer", example=350),
     *             @OA\Property(property="total_prescriptions", type="integer", example=200),
     *             @OA\Property(property="total_rendez_vous", type="integer", example=120),
     *             @OA\Property(property="total_resultats_analyses", type="integer", example=90),
     *             @OA\Property(property="total_centres_sante", type="integer", example=10),
     *             @OA\Property(property="consultations_ce_mois", type="integer", example=30),
     *             @OA\Property(property="rdv_en_attente", type="integer", example=15),
     *             @OA\Property(property="rdv_aujourdhui", type="integer", example=5),
     *             @OA\Property(property="patients_par_mois", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="consultations_par_mois", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="top_pathologies", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="repartition_par_role", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
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
                DB::raw("EXTRACT(MONTH FROM created_at) as mois"),
                DB::raw('COUNT(*) as total')
            )->whereYear('created_at', now()->year)->groupBy('mois')->orderBy('mois')->get(),

            'consultations_par_mois' => Consultation::select(
                DB::raw("EXTRACT(MONTH FROM date) as mois"),
                DB::raw('COUNT(*) as total')
            )->whereYear('date', now()->year)->groupBy('mois')->orderBy('mois')->get(),

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

    /**
     * @OA\Get(
     *     path="/api/admin/statistiques/centre/{id}",
     *     tags={"Admin - Statistiques"},
     *     summary="Statistiques d'un centre de santé",
     *     description="Retourne les statistiques d'activité pour un centre de santé spécifique. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID du centre de santé",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques du centre",
     *         @OA\JsonContent(
     *             @OA\Property(property="centre", type="string", example="Hôpital Principal de Dakar"),
     *             @OA\Property(property="nombre_medecins", type="integer", example=12),
     *             @OA\Property(property="total_consultations", type="integer", example=450),
     *             @OA\Property(property="consultations_ce_mois", type="integer", example=40),
     *             @OA\Property(property="total_prescriptions", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Centre non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
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
