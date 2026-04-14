<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProtectedRoutesCoverageTest extends TestCase
{
    /**
     * Vérifie que les nouvelles routes protégées existent bien
     * et rejettent les accès sans authentification.
     */
    public function test_nouvelles_routes_protegees_retournent_401_sans_token(): void
    {
        $routes = [
            '/api/patient/teleconsultations',
            '/api/patient/constantes-vitales',
            '/api/patient/constantes-vitales/latest',
            '/api/patient/constantes-vitales/historique?type=temperature',
            '/api/pharmacien/ordonnances',
            '/api/pharmacien/delivrances',
            '/api/laborantin/demandes',
            '/api/laborantin/resultats',
            '/api/iot/constantes',
            '/api/iot/devices?patient_id=1',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(401);
        }
    }

    /**
     * Vérifie que les routes POST protégées ajoutées exigent aussi un token.
     */
    public function test_nouvelles_routes_post_protegees_retournent_401_sans_token(): void
    {
        $payloads = [
            '/api/pharmacien/delivrances' => ['prescription_id' => 1],
            '/api/laborantin/resultats' => [
                'demande_analyse_id' => 1,
                'type_analyse' => 'NFS',
                'resultats' => 'Résultat de test',
            ],
            '/api/iot/constantes' => [
                'patient_id' => 1,
                'type' => 'temperature',
                'valeur' => 37.2,
            ],
            '/api/iot/constantes/sync' => [
                'patient_id' => 1,
                'device_id' => 'device-demo',
                'mesures' => [
                    [
                        'type' => 'temperature',
                        'valeur' => 37.2,
                    ],
                ],
            ],
        ];

        foreach ($payloads as $route => $payload) {
            $response = $this->postJson($route, $payload);
            $response->assertStatus(401);
        }
    }
}
