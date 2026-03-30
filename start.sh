#!/bin/bash
set -e

echo "========================================="
echo "   DocsecurBackend - Démarrage local     "
echo "========================================="

# Créer le fichier .env si nécessaire
if [ ! -f .env ]; then
    echo "📝 Création du .env depuis .env.example..."
    cp .env.example .env
fi

# Afficher la configuration DB active
echo "🗄️  Configuration base de données :"
echo "   DB_CONNECTION : ${DB_CONNECTION:-pgsql}"
echo "   DB_HOST       : ${DB_HOST:-non défini}"
echo "   DB_PORT       : ${DB_PORT:-5432}"
echo "   DB_DATABASE   : ${DB_DATABASE:-non défini}"

# Générer APP_KEY si absent
if [ -z "$APP_KEY" ] || [[ "$APP_KEY" != base64:* ]]; then
    echo "🔑 Génération de l'APP_KEY..."
    php artisan key:generate --force
fi

# Tester la connexion PostgreSQL
echo "⏳ Connexion à PostgreSQL..."
MAX_TRIES=15
COUNT=0
until php -r "
try {
    \$sslmode = getenv('DB_SSLMODE') ?: 'prefer';
    \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . getenv('DB_DATABASE') . ';sslmode=' . \$sslmode;
    new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 5]);
    echo 'PostgreSQL connecté !';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "❌ Impossible de se connecter à PostgreSQL après ${MAX_TRIES} tentatives."
        exit 1
    fi
    echo "   PostgreSQL non disponible, tentative ${COUNT}/${MAX_TRIES}..."
    sleep 2
done
echo "✅ PostgreSQL prêt !"

# Migrations
echo "📦 Exécution des migrations..."
php artisan migrate --force

# OAuth keys Passport
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "🔐 Génération des clés OAuth Passport..."
    php artisan passport:keys --force
fi

# Optimisation
echo "⚡ Optimisation de l'application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:cache

echo "✅ Prêt ! Démarrage de Laravel..."
exec "$@"
