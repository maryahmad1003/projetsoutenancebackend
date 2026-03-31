<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\CarnetVaccination;
use App\Models\Vaccin;
use Illuminate\Http\Request;

class CarnetVaccinationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/patient/vaccination",
     *     tags={"Patient - Vaccination"},
     *     summary="Carnet de vaccination du patient connecté",
     *     description="Retourne le carnet de vaccination du patient authentifié avec la liste des vaccins administrés.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Carnet de vaccination",
     *         @OA\JsonContent(type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="patient_id", type="integer"),
     *             @OA\Property(property="vaccins", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function monCarnet(Request $request)
    {
        $patient = $request->user()->patient;
        $carnet = CarnetVaccination::where('patient_id', $patient->id)
            ->with(['vaccins.medecin.user'])
            ->first();

        return response()->json($carnet);
    }

    /**
     * @OA\Get(
     *     path="/api/patient/vaccination/vaccins",
     *     tags={"Patient - Vaccination"},
     *     summary="Ajouter un vaccin au carnet (accessible via GET)",
     *     description="Endpoint pour l'ajout d'un vaccin au carnet de vaccination d'un patient.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Vaccin ajouté", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function ajouterVaccin(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'nom' => 'required|string',
            'date_administration' => 'required|date',
            'date_rappel' => 'nullable|date',
            'lot' => 'nullable|string',
        ]);

        $carnet = CarnetVaccination::where('patient_id', $request->patient_id)->first();

        $vaccin = Vaccin::create([
            'carnet_vaccination_id' => $carnet->id,
            'medecin_id' => $request->user()->medecin->id ?? null,
            'nom' => $request->nom,
            'date_administration' => $request->date_administration,
            'date_rappel' => $request->date_rappel,
            'lot' => $request->lot,
        ]);

        return response()->json(['message' => 'Vaccin ajouté', 'vaccin' => $vaccin], 201);
    }

    public function show(Request $request)
    {
        return $this->monCarnet($request);
    }

    public function vaccins(Request $request)
    {
        return $this->ajouterVaccin($request);
    }
}
