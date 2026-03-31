<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Vérifie que le endpoint GET /api/health retourne HTTP 200.
     *
     * C'est le test minimal utilisé par le CI/CD pour valider
     * que l'application démarre correctement.
     */
    public function test_health_check_retourne_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'service', 'timestamp'])
                 ->assertJsonFragment(['status' => 'ok', 'service' => 'DocSecur API']);
    }

    /**
     * Vérifie que les routes non authentifiées publiques fonctionnent.
     */
    public function test_route_publique_login_existe(): void
    {
        // Tester que la route POST /api/login est accessible (même si les credentials sont faux)
        $response = $this->postJson('/api/login', []);

        // 422 = validation échouée (route existe et répond)
        $response->assertStatus(422);
    }

    /**
     * Vérifie que les routes protégées rejettent les requêtes non authentifiées.
     */
    public function test_route_protegee_retourne_401_sans_token(): void
    {
        $response = $this->getJson('/api/profil');

        $response->assertStatus(401);
    }
}
