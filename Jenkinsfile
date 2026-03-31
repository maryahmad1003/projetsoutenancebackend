// =============================================================================
// DocSecur Backend — Jenkinsfile
// Pipeline CI/CD équivalent à .github/workflows/ci-cd.yml
//
// Prérequis Jenkins plugins :
//   - Pipeline
//   - Docker Pipeline
//   - Credentials Binding
//   - Workspace Cleanup
//
// Credentials à créer dans Jenkins (Manage Jenkins > Credentials) :
//   - RENDER_DEPLOY_HOOK_URL  → Secret text
//   - RENDER_APP_URL          → Secret text
// =============================================================================

pipeline {
    agent any

    // ── Options globales ──────────────────────────────────────────────────────
    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 30, unit: 'MINUTES')
        skipStagesAfterUnstable()
    }

    // ── Déclencheurs ──────────────────────────────────────────────────────────
    triggers {
        // Poll SCM toutes les 5 minutes (optionnel si webhook GitHub configuré)
        pollSCM('H/5 * * * *')
    }

    // ── Variables d'environnement pour les tests ──────────────────────────────
    environment {
        APP_NAME            = 'DocSecur'
        APP_ENV             = 'testing'
        APP_DEBUG           = 'true'
        APP_URL             = 'http://localhost'

        // Base de données de test (PostgreSQL via Docker)
        DB_CONNECTION       = 'pgsql'
        DB_HOST             = '127.0.0.1'
        DB_PORT             = '5432'
        DB_DATABASE         = 'docsecur_test'
        DB_USERNAME         = 'docsecur_ci'
        DB_PASSWORD         = 'ci_secret_password'
        DB_SSLMODE          = 'disable'

        // Cache / Session / Queue en mémoire pour les tests
        CACHE_DRIVER        = 'array'
        SESSION_DRIVER      = 'array'
        QUEUE_CONNECTION    = 'sync'

        // Mail fictif
        MAIL_MAILER         = 'array'
        MAIL_FROM_ADDRESS   = 'test@docsecur.sn'

        // Swagger — host fictif pour la génération en CI
        L5_SWAGGER_CONST_HOST    = 'http://localhost'
        L5_SWAGGER_GENERATE_ALWAYS = 'true'

        // BCrypt réduit pour des tests plus rapides
        BCRYPT_ROUNDS       = '4'

        // Credentials Render (définis dans Jenkins > Credentials)
        RENDER_DEPLOY_HOOK_URL = credentials('RENDER_DEPLOY_HOOK_URL')
        RENDER_APP_URL         = credentials('RENDER_APP_URL')
    }

    stages {

        // ── STAGE 0 : Nettoyage ───────────────────────────────────────────────
        stage('Nettoyage workspace') {
            steps {
                cleanWs()
                checkout scm
                echo "✅ Code récupéré — Branche : ${env.BRANCH_NAME} | Commit : ${env.GIT_COMMIT[0..7]}"
            }
        }

        // ── STAGE 1 : Démarrer PostgreSQL de test ─────────────────────────────
        stage('Démarrer PostgreSQL') {
            steps {
                script {
                    // Arrêter et supprimer un éventuel conteneur précédent
                    sh 'docker rm -f docsecur_postgres_test 2>/dev/null || true'

                    // Lancer PostgreSQL 15 en conteneur Docker
                    sh '''
                        docker run -d \
                            --name docsecur_postgres_test \
                            -e POSTGRES_DB=docsecur_test \
                            -e POSTGRES_USER=docsecur_ci \
                            -e POSTGRES_PASSWORD=ci_secret_password \
                            -p 5432:5432 \
                            postgres:15-alpine

                        echo "⏳ Attente que PostgreSQL soit prêt..."
                        for i in $(seq 1 30); do
                            docker exec docsecur_postgres_test pg_isready -U docsecur_ci && break
                            echo "  Tentative $i/30..."
                            sleep 2
                        done
                        echo "✅ PostgreSQL prêt"
                    '''
                }
            }
        }

        // ── STAGE 2 : Installation des dépendances Composer ───────────────────
        stage('Composer Install') {
            steps {
                sh '''
                    echo "📦 Installation des dépendances PHP..."
                    composer install \
                        --no-interaction \
                        --prefer-dist \
                        --optimize-autoloader \
                        --no-progress
                    echo "✅ Composer install terminé"
                '''
            }
        }

        // ── STAGE 3 : Préparer l'environnement ────────────────────────────────
        stage('Préparer .env') {
            steps {
                sh '''
                    echo "⚙️  Préparation du fichier .env..."
                    cp .env.example .env

                    # Injecter les variables de test dans le .env
                    php artisan config:clear 2>/dev/null || true

                    php artisan key:generate --force
                    echo "✅ APP_KEY générée"

                    php artisan passport:keys --force
                    echo "✅ Clés Passport générées"
                '''
            }
        }

        // ── STAGE 4 : Migrations ──────────────────────────────────────────────
        stage('Migrations DB') {
            steps {
                sh '''
                    echo "🗄️  Exécution des migrations..."
                    php artisan migrate --force --no-interaction
                    echo "✅ Migrations terminées"
                '''
            }
        }

        // ── STAGE 5 : Tests PHPUnit ───────────────────────────────────────────
        stage('Tests PHPUnit') {
            steps {
                sh '''
                    echo "🧪 Lancement des tests PHPUnit..."
                    php artisan test --stop-on-failure
                '''
            }
            post {
                failure {
                    echo '❌ Tests échoués — pipeline arrêté'
                }
                success {
                    echo '✅ Tous les tests passent'
                }
            }
        }

        // ── STAGE 6 : Génération Swagger ──────────────────────────────────────
        stage('Swagger Docs') {
            steps {
                sh '''
                    echo "📖 Génération de la documentation Swagger..."
                    php artisan l5-swagger:generate

                    # Vérifier que le fichier a bien été généré
                    if [ ! -f storage/api-docs/api-docs.json ]; then
                        echo "❌ Erreur : storage/api-docs/api-docs.json non généré"
                        exit 1
                    fi

                    # Valider que le JSON est valide
                    python3 -c "import json; json.load(open('storage/api-docs/api-docs.json'))" \
                        && echo "✅ api-docs.json valide ($(wc -c < storage/api-docs/api-docs.json) octets)"
                '''
            }
            post {
                success {
                    // Archiver le fichier Swagger comme artefact Jenkins
                    archiveArtifacts artifacts: 'storage/api-docs/api-docs.json',
                                     fingerprint: true
                }
            }
        }

        // ── STAGE 7 : Linting avec Laravel Pint ──────────────────────────────
        stage('Linting Pint') {
            steps {
                sh '''
                    echo "🔍 Vérification du style de code (Pint)..."
                    ./vendor/bin/pint --test
                    echo "✅ Aucune violation de style détectée"
                '''
            }
            post {
                failure {
                    echo '❌ Problèmes de style détectés — lancez : ./vendor/bin/pint pour auto-corriger'
                }
            }
        }

        // ── STAGE 8 : Déploiement Render (main uniquement) ────────────────────
        stage('Déploiement Render') {
            // Déployer UNIQUEMENT sur la branche main
            when {
                branch 'main'
            }
            steps {
                script {
                    echo '🚀 Déclenchement du déploiement sur Render...'

                    def httpStatus = sh(
                        script: '''
                            curl --silent --output /dev/null --write-out "%{http_code}" \
                                --max-time 30 \
                                "${RENDER_DEPLOY_HOOK_URL}"
                        ''',
                        returnStdout: true
                    ).trim()

                    if (!['200','201','202'].contains(httpStatus)) {
                        error("❌ Deploy hook Render a retourné HTTP ${httpStatus}")
                    }
                    echo "✅ Deploy hook déclenché (HTTP ${httpStatus})"
                }
            }
        }

        // ── STAGE 9 : Health Check après déploiement ──────────────────────────
        stage('Health Check') {
            when {
                branch 'main'
            }
            steps {
                sh '''
                    echo "⏳ Attente de 90 secondes pour le démarrage du service Render..."
                    sleep 90

                    HEALTH_URL="${RENDER_APP_URL}/api/health"
                    MAX_ATTEMPTS=10
                    INTERVAL=30
                    ATTEMPT=0

                    echo "🔍 Vérification de $HEALTH_URL"

                    until [ $ATTEMPT -ge $MAX_ATTEMPTS ]; do
                        ATTEMPT=$((ATTEMPT + 1))
                        echo "Tentative $ATTEMPT/$MAX_ATTEMPTS..."

                        HTTP_STATUS=$(curl --silent --output /dev/null --write-out "%{http_code}" \
                            --max-time 15 \
                            "$HEALTH_URL" 2>/dev/null || echo "000")

                        if [ "$HTTP_STATUS" -eq 200 ]; then
                            echo "✅ Health check OK (HTTP 200) — Déploiement réussi !"
                            exit 0
                        fi

                        echo "   → HTTP $HTTP_STATUS — Nouvelle tentative dans ${INTERVAL}s..."
                        sleep $INTERVAL
                    done

                    echo "❌ Health check échoué après $MAX_ATTEMPTS tentatives"
                    exit 1
                '''
            }
            post {
                success {
                    echo "🎉 Déploiement DocSecur réussi !"
                }
            }
        }

    } // fin stages

    // ── Post-pipeline : nettoyage PostgreSQL ──────────────────────────────────
    post {
        always {
            sh 'docker rm -f docsecur_postgres_test 2>/dev/null || true'
            echo "🧹 Conteneur PostgreSQL nettoyé"
        }
        success {
            echo "✅ Pipeline terminé avec succès — Branche : ${env.BRANCH_NAME}"
        }
        failure {
            echo "❌ Pipeline échoué — Consulte les logs ci-dessus"
        }
    }

}
