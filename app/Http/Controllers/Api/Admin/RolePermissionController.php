<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class RolePermissionController extends Controller
{
    public function index()
    {
        $matrix = [
            [
                'role' => 'administrateur',
                'label' => 'Administrateur',
                'permissions' => [
                    'Gerer les utilisateurs',
                    'Attribuer ou changer les roles',
                    'Gerer les centres de sante',
                    'Gerer les campagnes',
                    'Consulter les statistiques globales',
                    'Superviser la securite',
                    'Exporter les donnees CSV/PDF',
                ],
            ],
            [
                'role' => 'medecin',
                'label' => 'Medecin',
                'permissions' => [
                    'Creer et mettre a jour les dossiers patients',
                    'Effectuer des consultations',
                    'Rediger des prescriptions electroniques',
                    'Demander des analyses biologiques',
                    'Planifier des teleconsultations',
                    'Consulter les constantes vitales et le suivi medical',
                ],
            ],
            [
                'role' => 'patient',
                'label' => 'Patient',
                'permissions' => [
                    'Consulter son dossier medical',
                    'Acceder aux prescriptions et resultats',
                    'Prendre des rendez-vous',
                    'Participer a une teleconsultation',
                    'Recevoir des notifications et rappels',
                    'Generer son QR code',
                ],
            ],
            [
                'role' => 'pharmacien',
                'label' => 'Pharmacien',
                'permissions' => [
                    'Consulter les ordonnances recues',
                    'Verifier le statut des prescriptions',
                    'Valider la delivrance',
                    'Notifier le patient',
                    'Consulter l historique des delivrances',
                ],
            ],
            [
                'role' => 'laborantin',
                'label' => 'Laborantin',
                'permissions' => [
                    'Consulter les demandes d analyses',
                    'Saisir les resultats biologiques',
                    'Transmettre les resultats au dossier patient',
                    'Notifier le medecin prescripteur',
                ],
            ],
        ];

        $summary = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return response()->json([
            'matrix' => array_map(function (array $item) use ($summary) {
                $item['total_users'] = (int) ($summary[$item['role']] ?? 0);
                return $item;
            }, $matrix),
            'assignable_roles' => array_column($matrix, 'role'),
        ]);
    }
}
