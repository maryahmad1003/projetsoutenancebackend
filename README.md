# DocSecur Backend

API Laravel du projet de soutenance `DocSecur`, une plateforme de centralisation et de suivi des données médicales.

## Fonctionnalités principales

- Authentification email/mot de passe et OTP patient
- Gestion multi-rôles : administrateur, médecin, patient, pharmacien, laborantin
- Dossiers médicaux, consultations, prescriptions et résultats d'analyses
- Téléconsultation avec lien vidéo Jitsi
- Rendez-vous et disponibilités médecin
- Notifications, messagerie et export CSV/PDF
- Interopérabilité FHIR
- Suivi des constantes vitales et endpoints IoT

## Stack

- Laravel 10
- PHP 8.1+
- PostgreSQL
- Laravel Passport
- Swagger `l5-swagger`
- DomPDF / Laravel Excel / Simple QR Code

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer ensuite la base de données dans `.env`.

## Démarrage local

```bash
php artisan migrate
php artisan serve
```

API locale par défaut :

```txt
http://localhost:8000/api
```

## Variables utiles

Voir [`.env.example`](/home/mary-vonne/Bureau/PROJETSOUTENANCE/docsecur-backend/.env.example).

Points importants :

- `DB_*` : connexion PostgreSQL
- `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`
- `APP_URL`
- `L5_SWAGGER_CONST_HOST`

## Routes métier principales

- `POST /api/register`
- `POST /api/login`
- `GET /api/health`
- `GET /api/profil`
- `GET /api/admin/*`
- `GET /api/medecin/*`
- `GET /api/patient/*`
- `GET /api/pharmacien/*`
- `GET /api/laborantin/*`
- `GET /api/iot/*`
- `GET /api/fhir/*`

## Tests

```bash
php artisan test
```

## Vérification rapide

- `GET /api/health` doit retourner `status=ok`
- les routes protégées doivent retourner `401` sans token
- Swagger est configuré via `l5-swagger`

## Remarques soutenance

- Le backend expose maintenant les routes pharmacien, laborantin, téléconsultation patient et constantes vitales patient.
- Les tests présents couvrent surtout l'existence des routes et la protection minimale.
- Un renforcement sécurité reste possible sur la stratégie de gestion du token côté frontend.
