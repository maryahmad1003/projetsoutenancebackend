<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
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