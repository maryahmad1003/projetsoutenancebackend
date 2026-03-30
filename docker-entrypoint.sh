#!/bin/sh
set -e

echo "========================================="
echo "   DocsecurBackend - Démarrage           "
echo "========================================="

# ─── 1. Générer le fichier .env depuis les variables d'environnement ───────────
echo "📝 Génération du .env..."
cat > /var/www/html/.env << ENVEOF
APP_NAME=${APP_NAME:-DocSecur}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost:8000}

LOG_CHANNEL=${LOG_CHANNEL:-stderr}
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-neondb}
DB_USERNAME=${DB_USERNAME:-neondb_owner}
DB_PASSWORD=${DB_PASSWORD:-}
DB_SSLMODE=${DB_SSLMODE:-require}

BROADCAST_DRIVER=log
CACHE_DRIVER=${CACHE_DRIVER:-file}
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=120

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_HOST=${MAIL_HOST:-localhost}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@docsecur.sn}
MAIL_FROM_NAME=DocSecur

TWILIO_SID=${TWILIO_SID:-}
TWILIO_TOKEN=${TWILIO_TOKEN:-}
TWILIO_FROM=${TWILIO_FROM:-}

CORS_ALLOWED_ORIGINS=${CORS_ALLOWED_ORIGINS:-*}
ENVEOF

# ─── 2. Générer APP_KEY si absent ──────────────────────────────────────────────
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "🔑 Génération de l'APP_KEY..."
    php artisan key:generate --force
fi

# ─── 3. Tester la connexion à la base de données (PostgreSQL / Neon) ──────────
echo "🗄️  Vérification de la connexion PostgreSQL..."
MAX_TRIES=10
COUNT=0
until php -r "
try {
    \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE') . ';sslmode=' . getenv('DB_SSLMODE');
    new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 5]);
    echo 'OK';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "❌ Impossible de se connecter à PostgreSQL après ${MAX_TRIES} tentatives."
        exit 1
    fi
    echo "⏳ PostgreSQL non disponible, tentative ${COUNT}/${MAX_TRIES}..."
    sleep 3
done
echo "✅ PostgreSQL connecté !"

# ─── 4. Migrations ────────────────────────────────────────────────────────────
echo "📦 Exécution des migrations..."
php artisan migrate --force

# ─── 5. Clés OAuth Passport ───────────────────────────────────────────────────
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "🔐 Génération des clés OAuth Passport..."
    php artisan passport:keys --force
fi

# ─── 6. Optimisation Laravel ──────────────────────────────────────────────────
echo "⚡ Optimisation de l'application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache

echo "========================================="
echo "   ✅ Application prête sur le port 8000 "
echo "========================================="

exec "$@"
