# DocSecur Backend — CLAUDE.md

## Vue d'ensemble

API REST Laravel 10 pour la plateforme de gestion médicale **DocSecur** (projet de soutenance). Gère les dossiers médicaux, consultations, prescriptions, téléconsultations, analyses et messagerie interne.

- **PHP** : 8.1+
- **Framework** : Laravel 10
- **Base de données** : PostgreSQL (Neon.tech, cloud)
- **Authentification** : Laravel Passport (OAuth2, Bearer tokens)
- **Rôles** : Spatie Laravel Permission
- **Swagger** : darkaonline/l5-swagger 8.6 (OpenAPI 3.0)

---

## Commandes essentielles

```bash
# Démarrer le serveur
php artisan serve

# Générer la documentation Swagger
php artisan l5-swagger:generate

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Vider les caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear
```

---

## Architecture des routes API

Toutes les routes sont sous le préfixe `/api`. Authentification Bearer requis sauf mention contraire.

| Préfixe | Middleware rôle | Description |
|---------|-----------------|-------------|
| `/api/register`, `/api/login` | aucun (throttle 5/min) | Auth publique |
| `/api/admin/*` | `role:administrateur` | Gestion système |
| `/api/medecin/*` | `role:medecin` | Espace médecin |
| `/api/patient/*` | `role:patient` | Espace patient |
| `/api/pharmacien/*` | `role:pharmacien` | Espace pharmacien |
| `/api/laborantin/*` | `role:laborantin` | Espace laborantin |
| `/api/messages/*`, `/api/notifications/*` | authentifié | Commun à tous |

Fichier de routes : [routes/api.php](routes/api.php)

---

## Structure des controllers

```
app/Http/Controllers/
├── Controller.php                  ← Annotation @OA\Info Swagger ici
└── Api/
    ├── Auth/AuthController.php
    ├── Admin/
    │   ├── UserManagementController.php
    │   ├── CentreSanteController.php
    │   ├── CampagneController.php
    │   └── StatistiqueController.php
    ├── ExportController.php        ← méthodes: patientsCSV, consultationsCSV, statistiquesPDF
    ├── NotificationController.php
    ├── MessageController.php
    ├── Medecin/
    │   ├── ConsultationController.php  ← contient aussi getPatients, getPatient, getHistorique
    │   ├── PrescriptionController.php
    │   ├── TeleconsultationController.php
    │   └── DemandeAnalyseController.php
    ├── Patient/
    │   ├── DossierMedicalController.php  ← contient aussi creerPatient, updatePatient
    │   ├── RendezVousController.php
    │   ├── CarnetVaccinationController.php
    │   └── QRCodeController.php
    ├── Pharmacien/
    │   ├── OrdonnanceController.php
    │   └── DelivranceController.php
    └── Laborantin/
        ├── DemandeAnalyseController.php
        └── ResultatAnalyseController.php
```

---

## Modèles principaux

| Modèle | Table | Description |
|--------|-------|-------------|
| `User` | `users` | Compte utilisateur, champ `role` |
| `Patient` | `patients` | Profil patient, `num_dossier` format `DS-XXXXXX` |
| `Medecin` | `medecins` | Profil médecin, lié à `CentreSante` |
| `DossierMedical` | `dossiers_medicaux` | Dossier médical, `numero_dossier` format `DM-XXXXXX` |
| `Consultation` | `consultations` | Constantes vitales + données médicales, IMC auto-calculé |
| `Prescription` | `prescriptions` | Numéro auto `RX-XXXXXXXX`, expiration 3 mois |
| `Teleconsultation` | `teleconsultations` | Lien Jitsi auto-généré `https://meet.jit.si/docsecur-XXXXXXXX` |
| `DemandeAnalyse` | `demandes_analyses` | Demande labo, statuts : `envoyee`, `en_cours`, `terminee` |
| `ResultatAnalyse` | `resultats_analyses` | Résultat labo |
| `RendezVous` | `rendez_vous` | Statuts : `en_attente`, `confirme`, `annule` |
| `Message` | `messages` | Messagerie interne, support fichier joint |
| `Notification` | `notifications` | Canaux : `sms`, `application` |
| `TableauDeBord` | `tableaux_bord` | Fichier : `app/Models/TableauDeBord.php` |

---

## Documentation Swagger

- **Interface UI** : `http://localhost:8000/api/documentation`
- **JSON OpenAPI** : `http://localhost:8000/docs`
- **Fichier généré** : `storage/api-docs/api-docs.json`
- **Annotations** : dans chaque controller via `@OA\*`
- **Sécurité** : Bearer token dans l'annotation `@OA\SecurityScheme("bearerAuth")`

Variables `.env` nécessaires :
```
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_SWAGGER_GENERATE_ALWAYS=true
```

---

## Points d'attention importants

### Noms de méthodes vs routes
Certaines routes appellent des méthodes dont le nom diffère de la convention REST :

| Route | Méthode appelée |
|-------|----------------|
| `GET /api/admin/export/patients` | `ExportController::patientsCSV()` |
| `GET /api/admin/export/consultations` | `ExportController::consultationsCSV()` |
| `GET /api/admin/export/stats-pdf` | `ExportController::statistiquesPDF()` |
| `GET /api/patient/vaccination` | `CarnetVaccinationController::show()` → `monCarnet()` |
| `GET /api/patient/qrcode` | `QRCodeController::generer()` → `monQRCode()` |
| `PUT /api/patient/rendez-vous/{id}/annuler` | `RendezVousController::annuler()` |
| `PUT /api/patient/rendez-vous/{id}/modifier` | `RendezVousController::modifier()` |

### Nom de fichier modèle
- `app/Models/TableauDeBord.php` (classe `TableauDeBord`) — le fichier s'appelait `TableauBord.php` à l'origine, renommé pour respecter PSR-4.

### Création de patient par un médecin
`POST /api/medecin/patients` → `DossierMedicalController::creerPatient()` génère un mot de passe temporaire retourné en clair dans la réponse (à communiquer au patient).

---

## Déploiement (Render)

- Config : [render.yaml](render.yaml)
- Dockerfile : [Dockerfile](Dockerfile)
- Health check : `GET /api/health` (sans authentification)
- Base de données : PostgreSQL Neon.tech (cloud), SSL requis (`DB_SSLMODE=require`)

---

## CI/CD (GitHub Actions)

Pipeline défini dans [.github/workflows/ci-cd.yml](.github/workflows/ci-cd.yml).

### Déclencheurs
- **CI** : tout push ou PR vers `main` ou `develop`
- **CD** : push vers `main` uniquement (après succès du CI)

### Job CI — Tests & Qualité
1. Checkout du code
2. PHP 8.1 + extensions (`pdo_pgsql`, `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `tokenizer`, `curl`, `gd`, `zip`, `intl`)
3. Cache Composer (`vendor/`)
4. `composer install --no-interaction --prefer-dist --optimize-autoloader`
5. `cp .env.example .env && php artisan key:generate --force`
6. `php artisan passport:keys --force`
7. `php artisan migrate --force`
8. `php artisan test --stop-on-failure`
9. `php artisan l5-swagger:generate` + validation JSON
10. `./vendor/bin/pint --test` (lint sans modification)
11. Upload `storage/api-docs/api-docs.json` en artefact (7 jours)

PostgreSQL de test : image `postgres:15-alpine`, base `docsecur_test`, user `docsecur_ci`, password `ci_secret_password`.

### Job CD — Déploiement Render
1. Déclenche le deploy hook Render via `curl`
2. Attente 90 secondes (démarrage du service)
3. Health check sur `RENDER_APP_URL/api/health` — 10 tentatives × 30s
4. Résumé du déploiement

### GitHub Secrets à configurer

Dans **Settings → Secrets and variables → Actions** du repo GitHub :

| Secret | Description | Où le trouver |
|--------|-------------|---------------|
| `RENDER_DEPLOY_HOOK_URL` | URL du deploy hook Render | Dashboard Render → Service → Settings → Deploy Hook |
| `RENDER_APP_URL` | URL publique de l'API (ex: `https://docsecur-api.onrender.com`) | Dashboard Render → Service → URL |

### Lancer les tests en local

Prérequis : PostgreSQL local avec la base `docsecur_test`.

```bash
# Créer la base de test (une seule fois)
createdb docsecur_test

# Lancer les tests
php artisan test

# Linting (vérification sans modification)
./vendor/bin/pint --test

# Linting avec auto-correction
./vendor/bin/pint
```

### Template Pull Request

[.github/PULL_REQUEST_TEMPLATE.md](.github/PULL_REQUEST_TEMPLATE.md) — Template FR avec checklist : tests, Swagger, Pint, migrations réversibles.
