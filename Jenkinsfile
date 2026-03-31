// =============================================================================
// DocSecur Backend — Jenkinsfile
// Pipeline CI/CD équivalent à .github/workflows/ci-cd.yml
//
// Credentials à créer dans Jenkins (Manage Jenkins > Credentials) :
//   - RENDER_DEPLOY_HOOK_URL  → Secret text
//   - RENDER_APP_URL          → Secret text
// =============================================================================

pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 30, unit: 'MINUTES')
        skipStagesAfterUnstable()
    }

    // ── Variables d'environnement pour les tests ──────────────────────────────
    // NOTE : les credentials Render sont déclarés uniquement dans les stages
    //        qui en ont besoin (withCredentials) pour éviter l'erreur au démarrage
    environment {
        APP_NAME                   = 'DocSecur'
        APP_ENV                    = 'testing'
        APP_DEBUG                  = 'true'
        APP_URL                    = 'http://localhost'

        DB_CONNECTION              = 'pgsql'
        DB_HOST                    = '127.0.0.1'
        DB_PORT                    = '5432'
        DB_DATABASE                = 'docsecur_test'
        DB_USERNAME                = 'docsecur_ci'
        DB_PASSWORD                = 'ci_secret_password'
        DB_SSLMODE                 = 'disable'

        CACHE_DRIVER               = 'array'
        SESSION_DRIVER             = 'array'
        QUEUE_CONNECTION           = 'sync'

        MAIL_MAILER                = 'array'
        MAIL_FROM_ADDRESS          = 'test@docsecur.sn'

        L5_SWAGGER_CONST_HOST      = 'http://localhost'
        L5_SWAGGER_GENERATE_ALWAYS = 'true'

        BCRYPT_ROUNDS              = '4'
    }

    stages {

        // ── STAGE 0 : Checkout ────────────────────────────────────────────────
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm
                echo "✅ Code récupéré — Commit : ${env.GIT_COMMIT?.take(7)}"
            }
        }

        // ── STAGE 1 : Démarrer PostgreSQL ────────────────────────────────────
        stage('Démarrer PostgreSQL') {
            steps {
                sh '''
                    docker rm -f docsecur_postgres_test 2>/dev/null || true

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

        // ── STAGE 2 : Composer Install ───────────────────────────────────────
        stage('Composer Install') {
            steps {
                sh '''
                    composer install \
                        --no-interaction \
                        --prefer-dist \
                        --optimize-autoloader \
                        --no-progress
                    echo "✅ Composer install terminé"
                '''
            }
        }

        // ── STAGE 3 : Préparer .env ──────────────────────────────────────────
        stage('Préparer .env') {
            steps {
                sh '''
                    cp .env.example .env
                    php artisan key:generate --force
                    echo "✅ APP_KEY générée"

                    php artisan passport:keys --force
                    echo "✅ Clés Passport générées"
                '''
            }
        }

        // ── STAGE 4 : Migrations ─────────────────────────────────────────────
        stage('Migrations DB') {
            steps {
                sh '''
                    php artisan migrate --force --no-interaction
                    echo "✅ Migrations terminées"
                '''
            }
        }

        // ── STAGE 5 : Tests PHPUnit ──────────────────────────────────────────
        stage('Tests PHPUnit') {
            steps {
                sh 'php artisan test --stop-on-failure'
            }
            post {
                success { echo '✅ Tous les tests passent' }
                failure { echo '❌ Tests échoués' }
            }
        }

        // ── STAGE 6 : Swagger ────────────────────────────────────────────────
        stage('Swagger Docs') {
            steps {
                sh '''
                    php artisan l5-swagger:generate

                    if [ ! -f storage/api-docs/api-docs.json ]; then
                        echo "❌ api-docs.json non généré"
                        exit 1
                    fi

                    python3 -c "import json; json.load(open('storage/api-docs/api-docs.json'))" \
                        && echo "✅ api-docs.json valide"
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'storage/api-docs/api-docs.json',
                                     fingerprint: true
                }
            }
        }

        // ── STAGE 7 : Linting Pint ───────────────────────────────────────────
        stage('Linting Pint') {
            steps {
                sh '''
                    ./vendor/bin/pint --test
                    echo "✅ Style de code OK"
                '''
            }
            post {
                failure {
                    echo '❌ Erreurs de style — lancez : ./vendor/bin/pint pour corriger'
                }
            }
        }

        // ── STAGE 8 : Déploiement Render (main uniquement) ───────────────────
        stage('Déploiement Render') {
            when { branch 'main' }
            steps {
                withCredentials([
                    string(credentialsId: 'RENDER_DEPLOY_HOOK_URL', variable: 'DEPLOY_HOOK'),
                    string(credentialsId: 'RENDER_APP_URL', variable: 'APP_URL_RENDER')
                ]) {
                    sh '''
                        echo "🚀 Déclenchement du déploiement Render..."
                        HTTP_STATUS=$(curl --silent --output /dev/null --write-out "%{http_code}" \
                            --max-time 30 "${DEPLOY_HOOK}")

                        if [ "$HTTP_STATUS" != "200" ] && [ "$HTTP_STATUS" != "201" ] && [ "$HTTP_STATUS" != "202" ]; then
                            echo "❌ Deploy hook retourné HTTP $HTTP_STATUS"
                            exit 1
                        fi
                        echo "✅ Deploy hook déclenché (HTTP $HTTP_STATUS)"
                    '''
                }
            }
        }

        // ── STAGE 9 : Health Check ───────────────────────────────────────────
        stage('Health Check') {
            when { branch 'main' }
            steps {
                withCredentials([
                    string(credentialsId: 'RENDER_APP_URL', variable: 'APP_URL_RENDER')
                ]) {
                    sh '''
                        echo "⏳ Attente 90s pour le démarrage Render..."
                        sleep 90

                        HEALTH_URL="${APP_URL_RENDER}/api/health"
                        MAX_ATTEMPTS=10
                        INTERVAL=30
                        ATTEMPT=0

                        until [ $ATTEMPT -ge $MAX_ATTEMPTS ]; do
                            ATTEMPT=$((ATTEMPT + 1))
                            echo "Tentative $ATTEMPT/$MAX_ATTEMPTS..."
                            HTTP_STATUS=$(curl --silent --output /dev/null \
                                --write-out "%{http_code}" --max-time 15 "$HEALTH_URL" || echo "000")

                            if [ "$HTTP_STATUS" = "200" ]; then
                                echo "✅ Health check OK — Déploiement réussi !"
                                exit 0
                            fi
                            sleep $INTERVAL
                        done

                        echo "❌ Health check échoué"
                        exit 1
                    '''
                }
            }
        }

    } // fin stages

    // ── Post-pipeline ─────────────────────────────────────────────────────────
    post {
        always {
            // Nettoyage PostgreSQL dans un node (correction du bug MissingContextVariableException)
            node('built-in') {
                sh 'docker rm -f docsecur_postgres_test 2>/dev/null || true'
                echo "🧹 Conteneur PostgreSQL nettoyé"
            }
        }
        success {
            echo "✅ Pipeline réussi — Branche : ${env.BRANCH_NAME}"
        }
        failure {
            echo "❌ Pipeline échoué"
        }
    }

}
