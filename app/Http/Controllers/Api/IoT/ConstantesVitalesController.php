<?php

namespace App\Http\Controllers\Api\IoT;

use App\Http\Controllers\Controller;
use App\Models\ConstanteVitale;
use App\Models\Patient;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConstantesVitalesController extends Controller
{
    public function monIndex(Request $request)
    {
        $patient = $request->user()->patient;
        $request->merge(['patient_id' => $patient->id]);

        return $this->index($request);
    }

    public function monLatest(Request $request)
    {
        $patient = $request->user()->patient;
        $request->merge(['patient_id' => $patient->id]);

        return $this->latest($request);
    }

    public function monHistorique(Request $request)
    {
        $patient = $request->user()->patient;
        $request->merge(['patient_id' => $patient->id]);

        return $this->historique($request);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|string',
            'valeur' => 'required|numeric',
            'unite' => 'nullable|string|max:20',
            'device_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'mesure_at' => 'nullable|date',
        ]);

        if ($validator-> fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['source'] = $request->filled('device_id') ? 'iot' : 'manuel';
        $data['unite'] = $data['unite'] ?? $this->getUniteParDefaut($data['type']);
        $data['mesure_at'] = $data['mesure_at'] ?? now();

        $constante = ConstanteVitale::create($data);

        $alerte = ConstanteVitale::detecterAnomalie($data['type'], $data['valeur']);

        if (in_array($alerte['statut'], ['alerte', 'anomalie'])) {
            $this->creerNotificationAlerte($constante, $alerte);
        }

        return response()->json([
            'success' => true,
            'data' => $constante,
            'alerte' => $alerte,
        ], 201);
    }

    public function index(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = ConstanteVitale::where('patient_id', $request->patient_id)
            ->orderBy('mesure_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from')) {
            $query->where('mesure_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('mesure_at', '<=', $request->to);
        }

        $limit = $request->integer('limit', 100);
        $constantes = $query->limit($limit)->get();

        $stats = $this->calculerStatistiques($constantes);

        return response()->json([
            'success' => true,
            'data' => $constantes,
            'stats' => $stats,
        ]);
    }

    public function latest(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'types' => 'nullable|array',
            'types.*' => 'string',
        ]);

        $types = $request->input('types');
        $query = ConstanteVitale::where('patient_id', $request->patient_id);

        if (!empty($types)) {
            $query->whereIn('type', $types);
        }

        $latestParType = [];
        foreach ($types ?? ConstanteVitale::typesDisponibles() as $type) {
            $latest = ConstanteVitale::where('patient_id', $request->patient_id)
                ->where('type', $type)
                ->orderBy('mesure_at', 'desc')
                ->first();
            if ($latest) {
                $latestParType[$type] = $latest;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $latestParType,
        ]);
    }

    public function historique(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|string',
            'periode' => 'nullable|in:24h,7j,30j,90j,personnalise',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $type = $request->type;
        $patientId = $request->patient_id;

        $query = ConstanteVitale::where('patient_id', $patientId)
            ->where('type', $type)
            ->orderBy('mesure_at', 'asc');

        $now = now();
        $periode = $request->periode ?? '7j';

        $from = match ($periode) {
            '24h' => $now->copy()->subHours(24),
            '7j' => $now->copy()->subDays(7),
            '30j' => $now->copy()->subDays(30),
            '90j' => $now->copy()->subDays(90),
            default => $request->filled('from') ? $request->from : $now->copy()->subDays(7),
        };

        $to = $request->filled('to') ? $request->to : $now;

        $donnees = $query->whereBetween('mesure_at', [$from, $to])->get();

        return response()->json([
            'success' => true,
            'type' => $type,
            'periode' => $periode,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'data' => $donnees,
            'count' => $donnees->count(),
        ]);
    }

    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'device_id' => 'required|string',
            'mesures' => 'required|array|min:1',
            'mesures.*.type' => 'required|string',
            'mesures.*.valeur' => 'required|numeric',
            'mesures.*.unite' => 'nullable|string',
            'mesures.*.mesure_at' => 'nullable|date',
            'mesures.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $patientId = $request->patient_id;
        $deviceId = $request->device_id;
        $mesures = $request->mesures;
        $resultats = [];

        foreach ($mesures as $mesure) {
            $data = [
                'patient_id' => $patientId,
                'device_id' => $deviceId,
                'type' => $mesure['type'],
                'valeur' => $mesure['valeur'],
                'unite' => $mesure['unite'] ?? $this->getUniteParDefaut($mesure['type']),
                'source' => 'iot',
                'mesure_at' => $mesure['mesure_at'] ?? now(),
                'notes' => $mesure['notes'] ?? null,
            ];

            $constante = ConstanteVitale::create($data);
            $alerte = ConstanteVitale::detecterAnomalie($data['type'], $data['valeur']);

            $resultats[] = [
                'id' => $constante->id,
                'type' => $data['type'],
                'alerte' => $alerte,
            ];
        }

        return response()->json([
            'success' => true,
            'synced' => count($resultats),
            'resultats' => $resultats,
        ], 201);
    }

    public function devices(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
        ]);

        $devices = ConstanteVitale::where('patient_id', $request->patient_id)
            ->whereNotNull('device_id')
            ->where('device_id', '!=', '')
            ->select('device_id', 'source')
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    private function getUniteParDefaut($type)
    {
        $unites = [
            'tension_systolique' => 'mmHg',
            'tension_diastolique' => 'mmHg',
            'glycemie' => 'g/L',
            'frequence_cardiaque' => 'bpm',
            'temperature' => '°C',
            'saturation_oxygene' => '%',
            'poids' => 'kg',
            'taille' => 'cm',
        ];

        return $unites[$type] ?? '';
    }

    private function calculerStatistiques($constantes)
    {
        if ($constantes->isEmpty()) {
            return null;
        }

        $parType = $constantes->groupBy('type');
        $stats = [];

        foreach ($parType as $type => $items) {
            $valeurs = $items->pluck('valeur');
            $stats[$type] = [
                'min' => $valeurs->min(),
                'max' => $valeurs->max(),
                'avg' => round($valeurs->avg(), 2),
                'count' => $valeurs->count(),
                'derniere_mesure' => $items->first()->mesure_at,
            ];
        }

        return $stats;
    }

    private function creerNotificationAlerte($constante, $alerte)
    {
        $patient = $constante->patient;
        if (!$patient || !$patient->user) {
            return;
        }

        $typesLabel = ConstanteVitale::typesDisponibles();
        $label = $typesLabel[$constante->type]['label'] ?? $constante->type;

        Notification::create([
            'user_id' => $patient->user->id,
            'type' => 'alerte_sante',
            'titre' => 'Alerte santé - ' . $label,
            'message' => $alerte['message'] . '. Valeur mesurée: ' . $constante->valeur . ' ' . $constante->unite,
            'canal' => 'application',
            'priorite' => $alerte['statut'] === 'alerte' ? 'haute' : 'moyenne',
            'action_url' => '/patient/constantes-vitales',
        ]);
    }
}
