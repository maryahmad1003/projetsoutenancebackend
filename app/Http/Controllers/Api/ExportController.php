<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/export/patients",
     *     tags={"Admin - Exports"},
     *     summary="Exporter la liste des patients en CSV",
     *     description="Télécharge un fichier CSV contenant tous les patients. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fichier CSV des patients",
     *         @OA\MediaType(mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function patientsCSV()
    {
        $patients = Patient::with('user')->get();

        $csv = "ID,Nom,Prénom,Email,Téléphone,Date Naissance,Sexe,Groupe Sanguin,Num Dossier\n";

        foreach ($patients as $patient) {
            $csv .= "{$patient->id},{$patient->user->nom},{$patient->user->prenom},{$patient->user->email},{$patient->user->telephone},{$patient->date_naissance},{$patient->sexe},{$patient->groupe_sanguin},{$patient->num_dossier}\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="patients_docsecur.csv"',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/export/consultations",
     *     tags={"Admin - Exports"},
     *     summary="Exporter les consultations en CSV",
     *     description="Télécharge un fichier CSV contenant toutes les consultations. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fichier CSV des consultations",
     *         @OA\MediaType(mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function consultationsCSV()
    {
        $consultations = Consultation::with(['medecin.user', 'dossierMedical.patient.user'])->get();

        $csv = "ID,Date,Patient,Médecin,Motif,Diagnostic,Tension,Poids,Type\n";

        foreach ($consultations as $c) {
            $patientNom = $c->dossierMedical->patient->user->nom ?? 'N/A';
            $medecinNom = $c->medecin->user->nom ?? 'N/A';
            $csv .= "{$c->id},{$c->date},{$patientNom},{$medecinNom},{$c->motif},{$c->diagnostic},{$c->tension},{$c->poids},{$c->type_consultation}\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="consultations_docsecur.csv"',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/export/stats-pdf",
     *     tags={"Admin - Exports"},
     *     summary="Exporter les statistiques en PDF",
     *     description="Télécharge un rapport PDF des statistiques globales. Rôle requis : administrateur.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fichier PDF des statistiques",
     *         @OA\MediaType(mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function statistiquesPDF()
    {
        $data = [
            'total_patients' => Patient::count(),
            'total_consultations' => Consultation::count(),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('exports.statistiques', $data);
        return $pdf->download('statistiques_docsecur.pdf');
    }
}
