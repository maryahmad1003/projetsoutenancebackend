<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\CentreSanteController;
use App\Http\Controllers\Api\Admin\CampagneController;
use App\Http\Controllers\Api\Admin\StatistiqueController;
use App\Http\Controllers\Api\Medecin\ConsultationController;
use App\Http\Controllers\Api\Medecin\PrescriptionController;
use App\Http\Controllers\Api\Medecin\TeleconsultationController;
use App\Http\Controllers\Api\Medecin\DemandeAnalyseController as MedecinDemandeAnalyseController;
use App\Http\Controllers\Api\Patient\DossierMedicalController;
use App\Http\Controllers\Api\Patient\RendezVousController;
use App\Http\Controllers\Api\Patient\CarnetVaccinationController;
use App\Http\Controllers\Api\Patient\QRCodeController;
use App\Http\Controllers\Api\Pharmacien\OrdonnanceController;
use App\Http\Controllers\Api\Pharmacien\DelivranceController;
use App\Http\Controllers\Api\Laborantin\DemandeAnalyseController as LaborantinDemandeAnalyseController;
use App\Http\Controllers\Api\Laborantin\ResultatAnalyseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| DocSecur API Routes
|--------------------------------------------------------------------------
*/

// ── Health Check (pour Render) ──────────────────────────────────────────────
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'DocSecur API',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ── Routes publiques (sans authentification) ──────────────────────────────
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ── OTP — Authentification patient par téléphone ──────────────────────────
Route::middleware('throttle:10,1')->prefix('auth')->group(function () {
    Route::post('/send-otp',    [OtpController::class, 'sendOtp']);
    Route::post('/verify-otp',  [OtpController::class, 'verifyOtp']);
});

// ── Routes protégées (avec token Passport) ────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/profil',           [AuthController::class, 'profil']);
    Route::put('/profil',           [AuthController::class, 'updateProfil']);
    Route::post('/changer-langue',  [AuthController::class, 'changerLangue']);

    // Notifications
    Route::get('/notifications',                [NotificationController::class, 'index']);
    Route::put('/notifications/tout-lues',      [NotificationController::class, 'marquerToutesLues']);
    Route::put('/notifications/{id}/lue',       [NotificationController::class, 'marquerLue']);
    Route::delete('/notifications/{id}',        [NotificationController::class, 'supprimer']);

    // ── Messagerie (tous les rôles) ───────────────────────────────────────
    Route::prefix('messages')->group(function () {
        Route::get('conversations',          [MessageController::class, 'conversations']);
        Route::get('non-lus',                [MessageController::class, 'nonLus']);
        Route::get('contacts',               [MessageController::class, 'contacts']);
        Route::get('{userId}',               [MessageController::class, 'index']);
        Route::post('/',                     [MessageController::class, 'store']);
    });

    // ── Administrateur ────────────────────────────────────────────────────
    Route::middleware('role:administrateur')->prefix('admin')->group(function () {
        Route::apiResource('utilisateurs',   UserManagementController::class);
        Route::apiResource('centres-sante',  CentreSanteController::class);
        Route::apiResource('campagnes',      CampagneController::class);
        Route::get('statistiques',           [StatistiqueController::class, 'index']);
        Route::get('statistiques/centre/{id}', [StatistiqueController::class, 'parCentre']);

        // Exports
        Route::get('export/patients',       [ExportController::class, 'patientsCSV']);
        Route::get('export/consultations',  [ExportController::class, 'consultationsCSV']);
        Route::get('export/stats-pdf',      [ExportController::class, 'statistiquesPDF']);
    });

    // ── Médecin ───────────────────────────────────────────────────────────
    Route::middleware('role:medecin')->prefix('medecin')->group(function () {
        // Patients
        Route::get('patients',         [ConsultationController::class, 'getPatients']);
        Route::post('patients',        [DossierMedicalController::class, 'creerPatient']);
        Route::get('patients/{id}',    [ConsultationController::class, 'getPatient']);
        Route::get('patients/{id}/historique', [ConsultationController::class, 'getHistorique']);
        Route::get('patients/{id}/constantes-vitales', [ConsultationController::class, 'getConstantesVitales']);
        Route::put('patients/{id}/update', [DossierMedicalController::class, 'updatePatient']);

        // Consultations
        Route::apiResource('consultations', ConsultationController::class);

        // Prescriptions
        Route::apiResource('prescriptions', PrescriptionController::class);
        Route::post('prescriptions/{id}/envoyer-pharmacie', [PrescriptionController::class, 'envoyerPharmacie']);

        // Téléconsultations
        Route::apiResource('teleconsultations', TeleconsultationController::class);
        Route::post('teleconsultations/{id}/demarrer',  [TeleconsultationController::class, 'demarrer']);
        Route::post('teleconsultations/{id}/terminer',  [TeleconsultationController::class, 'terminer']);

        // Demandes d'analyse
        Route::apiResource('demandes-analyse', MedecinDemandeAnalyseController::class);

        // Rendez-vous du médecin
        Route::get('rendez-vous', [ConsultationController::class, 'getRendezVous']);

        // QR Code
        Route::post('qrcode/scanner', [QRCodeController::class, 'scanner']);
    });

    // ── Patient ───────────────────────────────────────────────────────────
    Route::middleware('role:patient')->prefix('patient')->group(function () {
        // Dossier médical
        Route::get('dossier',          [DossierMedicalController::class, 'monDossier']);
        Route::get('historique',       [DossierMedicalController::class, 'monHistorique']);
        Route::get('prescriptions',    [DossierMedicalController::class, 'mesPrescriptions']);
        Route::get('resultats',        [DossierMedicalController::class, 'mesResultats']);

        // Carnet de vaccination
        Route::get('vaccination',           [CarnetVaccinationController::class, 'show']);
        Route::get('vaccination/vaccins',   [CarnetVaccinationController::class, 'vaccins']);

        // Rendez-vous
        Route::get('rendez-vous',           [RendezVousController::class, 'index']);
        Route::post('rendez-vous',          [RendezVousController::class, 'store']);
        Route::put('rendez-vous/{id}/annuler',  [RendezVousController::class, 'annuler']);
        Route::put('rendez-vous/{id}/modifier', [RendezVousController::class, 'modifier']);

        // QR Code
        Route::get('qrcode',           [QRCodeController::class, 'generer']);
    });

    // ── Pharmacien ────────────────────────────────────────────────────────
    Route::middleware('role:pharmacien')->prefix('pharmacien')->group(function () {
        Route::get('ordonnances',          [OrdonnanceController::class, 'index']);
        Route::get('ordonnances/{id}',     [OrdonnanceController::class, 'show']);
        Route::post('delivrances',         [DelivranceController::class, 'store']);
        Route::get('delivrances',          [DelivranceController::class, 'index']);
    });

    // ── Laborantin ────────────────────────────────────────────────────────
    Route::middleware('role:laborantin')->prefix('laborantin')->group(function () {
        Route::get('demandes',             [LaborantinDemandeAnalyseController::class, 'index']);
        Route::get('demandes/{id}',        [LaborantinDemandeAnalyseController::class, 'show']);
        Route::apiResource('resultats',    ResultatAnalyseController::class);
        Route::post('resultats/{id}/envoyer', [ResultatAnalyseController::class, 'envoyer']);
    });
});
