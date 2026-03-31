<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="DocSecur API",
 *     version="1.0.0",
 *     description="API REST pour la plateforme de gestion médicale DocSecur. Authentification via Bearer Token (Laravel Passport).",
 *     @OA\Contact(email="support@docsecur.sn")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur principal"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token"
 * )
 *
 * @OA\Tag(name="Authentification", description="Inscription, connexion, profil")
 * @OA\Tag(name="Admin - Utilisateurs", description="Gestion des utilisateurs (admin)")
 * @OA\Tag(name="Admin - Centres de santé", description="Gestion des centres de santé (admin)")
 * @OA\Tag(name="Admin - Campagnes", description="Gestion des campagnes de santé (admin)")
 * @OA\Tag(name="Admin - Statistiques", description="Statistiques globales (admin)")
 * @OA\Tag(name="Admin - Exports", description="Export de données CSV/PDF (admin)")
 * @OA\Tag(name="Notifications", description="Notifications utilisateurs")
 * @OA\Tag(name="Messagerie", description="Messagerie interne entre utilisateurs")
 * @OA\Tag(name="Médecin - Patients", description="Gestion des patients (médecin)")
 * @OA\Tag(name="Médecin - Consultations", description="Gestion des consultations (médecin)")
 * @OA\Tag(name="Médecin - Prescriptions", description="Gestion des prescriptions (médecin)")
 * @OA\Tag(name="Médecin - Téléconsultations", description="Gestion des téléconsultations (médecin)")
 * @OA\Tag(name="Médecin - Analyses", description="Demandes d'analyse (médecin)")
 * @OA\Tag(name="Patient - Dossier", description="Dossier médical du patient")
 * @OA\Tag(name="Patient - Rendez-vous", description="Gestion des rendez-vous (patient)")
 * @OA\Tag(name="Patient - Vaccination", description="Carnet de vaccination (patient)")
 * @OA\Tag(name="Patient - QR Code", description="QR Code du patient")
 * @OA\Tag(name="Pharmacien - Ordonnances", description="Consultation des ordonnances (pharmacien)")
 * @OA\Tag(name="Pharmacien - Délivrances", description="Validation de délivrance (pharmacien)")
 * @OA\Tag(name="Laborantin - Demandes", description="Demandes d'analyse (laborantin)")
 * @OA\Tag(name="Laborantin - Résultats", description="Résultats d'analyse (laborantin)")
 * @OA\Tag(name="Système", description="Endpoints système")
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
