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
use App\Http\Controllers\Api\IoT\ConstantesVitalesController;
use App\Http\Controllers\Api\Fhir\FhirController;
use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\Medecin\HoraireController;
use App\Http\Controllers\Api\MedecinController;
use App\Http\Controllers\Api\ChatbotController;

/*
|--------------------------------------------------------------------------
| DocSecur API Routes
|--------------------------------------------------------------------------
*/

// Health Check (pour Render)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'DocSecur API',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Routes publiques (sans authentification)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// OTP — Authentification patient par téléphone
Route::middleware('throttle:10,1')->prefix('auth')->group(function () {
    Route::post('/send-otp',    [OtpController::class, 'sendOtp']);
    Route::post('/verify-otp',  [OtpController::class, 'verifyOtp']);
});

// Liste des médecins disponibles (public)
Route::get('/medecins', [MedecinController::class, 'liste']);

// Routes protègees (avec token Passport)
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/profil',           [AuthController::class, 'profil']);
    Route::put('/profil',           [AuthController::class, 'updateProfil']);
    Route::post('/changer-langue',  [AuthController::class, 'changerLangue']);

    // Push Notifications
    Route::post('/notifications/subscribe',   [PushNotificationController::class, 'subscribe']);
    Route::post('/notifications/unsubscribe',   [PushNotificationController::class, 'unsubscribe']);
    Route::post('/notifications/send',        [PushNotificationController::class, 'send']);

    // Notifications
    Route::get('/notifications',                [NotificationController::class, 'index']);
    Route::put('/notifications/tout-lues',      [NotificationController::class, 'marquerToutesLues']);
    Route::put('/notifications/{id}/lue',       [NotificationController::class, 'marquerLue']);
    Route::delete('/notifications/{id}',        [NotificationController::class, 'supprimer']);

    // Messagerie (tous les rôles)
    Route::prefix('messages')->group(function () {
        Route::get('conversations',          [MessageController::class, 'conversations']);
        Route::get('non-lus',                [MessageController::class, 'nonLus']);
        Route::get('contacts',               [MessageController::class, 'contacts']);
        Route::get('{userId}',               [MessageController::class, 'index']);
        Route::post('/',                     [MessageController::class, 'store']);
    });

    // Administrateur
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

    // Medecin
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

        //Rendez-vous du medecin
        Route::get('rendez-vous', [ConsultationController::class, 'getRendezVous']);
        Route::get('rendez-vous/liste', [HoraireController::class, 'getRendezVousMedecin']);
        Route::put('rendez-vous/{id}/confirmer', [HoraireController::class, 'confirmerRendezVous']);
        Route::put('rendez-vous/{id}/refuser', [HoraireController::class, 'refuserRendezVous']);
        Route::put('rendez-vous/{id}/definir-date-heure', [HoraireController::class, 'definirDateHeure']);

        // Horaires et disponibilités
        Route::put('horaires', [HoraireController::class, 'definirHoraires']);
        Route::get('disponibilites/{medecinId}', [HoraireController::class, 'getDisponibilites']);

        // QR Code
        Route::post('qrcode/scanner', [QRCodeController::class, 'scanner']);
    });

    // Patient
    Route::middleware('role:patient')->prefix('patient')->group(function () {
        // Dossier médical
        Route::get('dossier',          [DossierMedicalController::class, 'monDossier']);
        Route::get('historique',       [DossierMedicalController::class, 'monHistorique']);
        Route::get('prescriptions',    [DossierMedicalController::class, 'mesPrescriptions']);
        Route::get('resultats',        [DossierMedicalController::class, 'mesResultats']);
        Route::get('teleconsultations', [TeleconsultationController::class, 'mesTeleconsultations']);

        // Carnet de vaccination
        Route::get('vaccination',           [CarnetVaccinationController::class, 'show']);
        Route::get('vaccination/vaccins',   [CarnetVaccinationController::class, 'vaccins']);

        // Rendez-vous
        Route::get('rendez-vous',           [RendezVousController::class, 'index']);
        Route::post('rendez-vous',          [RendezVousController::class, 'store']);
        Route::put('rendez-vous/{id}/annuler',  [RendezVousController::class, 'annuler']);
        Route::put('rendez-vous/{id}/confirmer', [RendezVousController::class, 'confirmer']);
        Route::put('rendez-vous/{id}/modifier', [RendezVousController::class, 'modifier']);
        Route::get('disponibilites/{medecinId}', [HoraireController::class, 'getDisponibilites']);

        // Constantes vitales
        Route::get('constantes-vitales', [ConstantesVitalesController::class, 'monIndex']);
        Route::get('constantes-vitales/latest', [ConstantesVitalesController::class, 'monLatest']);
        Route::get('constantes-vitales/historique', [ConstantesVitalesController::class, 'monHistorique']);

        // QR Code
        Route::get('qrcode', [QRCodeController::class, 'generer']);
    });

    // Pharmacien
    Route::middleware('role:pharmacien')->prefix('pharmacien')->group(function () {
        Route::get('ordonnances', [OrdonnanceController::class, 'index']);
        Route::get('ordonnances/{id}', [OrdonnanceController::class, 'show']);
        Route::get('delivrances', [DelivranceController::class, 'index']);
        Route::post('delivrances', [DelivranceController::class, 'store']);
    });

    // Laborantin
    Route::middleware('role:laborantin')->prefix('laborantin')->group(function () {
        Route::get('demandes', [LaborantinDemandeAnalyseController::class, 'index']);
        Route::get('demandes/{id}', [LaborantinDemandeAnalyseController::class, 'show']);
        Route::get('resultats', [ResultatAnalyseController::class, 'index']);
        Route::post('resultats', [ResultatAnalyseController::class, 'store']);
        Route::get('resultats/{id}', [ResultatAnalyseController::class, 'show']);
        Route::put('resultats/{id}', [ResultatAnalyseController::class, 'update']);
        Route::delete('resultats/{id}', [ResultatAnalyseController::class, 'destroy']);
        Route::post('resultats/{id}/envoyer', [ResultatAnalyseController::class, 'envoyer']);
    });

    // IoT / synchronisation objets connectés
    Route::prefix('iot')->group(function () {
        Route::post('constantes', [ConstantesVitalesController::class, 'store']);
        Route::get('constantes', [ConstantesVitalesController::class, 'index']);
        Route::post('constantes/sync', [ConstantesVitalesController::class, 'sync']);
        Route::get('devices', [ConstantesVitalesController::class, 'devices']);
    });

    // FHIR - Interopérabilité
    Route::prefix('fhir')->group(function () {
        Route::get('/status',              [FhirController::class, 'status']);
        Route::get('/patient/{id}',        [FhirController::class, 'exportPatient']);
        Route::get('/consultation/{id}',   [FhirController::class, 'exportConsultation']);
        Route::get('/patients',            [FhirController::class, 'exportAllPatients']);
        Route::post('/send',               [FhirController::class, 'sendToExternal']);
        Route::post('/receive',            [FhirController::class, 'receive']);
    });

    // Chatbot IA
    Route::prefix('chatbot')->group(function () {
        Route::post('/chat',               [ChatbotController::class, 'chat']);
        Route::get('/status',              [ChatbotController::class, 'status']);
    });
});
