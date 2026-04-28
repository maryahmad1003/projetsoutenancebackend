<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SecuriteController extends Controller
{
    private const SECURITY_SIGNAL_KEYWORDS = [
        'otp',
        'auth',
        'unauthor',
        'forbidden',
        'access denied',
        'login',
        'connexion refusee',
        'connexion echouee',
        'tentative',
        'throttle',
        'token invalide',
        'token expire',
        'mot de passe incorrect',
    ];

    private const TECHNICAL_NOISE_KEYWORDS = [
        'sqlstate',
        'pdoexception',
        'queryexception',
        'could not find driver',
        'connection:',
        'information_schema',
        'alter table',
        'table_catalog',
        'table_schema',
        'migrations',
        'stacktrace',
        '[object]',
        ' at /home/',
        'vendor/laravel',
        'session_replication_role',
    ];

    public function index()
    {
        $recentEvents = $this->extractSecurityEvents();
        $authProtectedRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return in_array('auth:api', $route->middleware(), true);
        });

        return response()->json([
            'security_score' => $this->computeSecurityScore($recentEvents),
            'auth_mode' => 'Laravel Passport Bearer Token',
            'throttle' => [
                'login' => true,
                'register' => true,
                'otp' => true,
            ],
            'encryption' => [
                'app_cipher' => config('app.cipher'),
                'medical_fields_encrypted' => true,
                'custom_key_configured' => !empty(config('app.encryption_key')),
            ],
            'accounts' => [
                'total' => User::count(),
                'active' => User::where('est_actif', true)->count(),
                'inactive' => User::where('est_actif', false)->count(),
                'admins' => User::where('role', 'administrateur')->count(),
            ],
            'access_control' => [
                'protected_routes' => $authProtectedRoutes->count(),
                'role_middleware_routes' => $authProtectedRoutes->filter(fn ($route) => collect($route->middleware())->contains(fn ($m) => str_starts_with($m, 'role:')))->count(),
                'roles_supported' => ['administrateur', 'medecin', 'patient', 'pharmacien', 'laborantin'],
            ],
            'journaux_acces' => $recentEvents,
        ]);
    }

    private function extractSecurityEvents(): array
    {
        $path = storage_path('logs/laravel.log');
        if (!File::exists($path)) {
            return [];
        }

        $lines = array_slice(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -250);

        return collect($lines)
            ->filter(fn ($line) => $this->isRelevantSecurityEvent($line))
            ->take(-12)
            ->values()
            ->map(function ($line) {
                preg_match('/^\[(.*?)\]\s+(\w+)\.(\w+):\s+(.*)$/', $line, $matches);

                return [
                    'timestamp' => $matches[1] ?? now()->toDateTimeString(),
                    'level' => strtoupper($matches[3] ?? 'INFO'),
                    'message' => substr($matches[4] ?? $line, 0, 240),
                ];
            })
            ->all();
    }

    private function isRelevantSecurityEvent(string $line): bool
    {
        $haystack = strtolower($line);

        foreach (self::TECHNICAL_NOISE_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return false;
            }
        }

        foreach (self::SECURITY_SIGNAL_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function computeSecurityScore(array $events): int
    {
        $score = 88;

        if (!config('app.encryption_key')) {
            $score -= 8;
        }

        $criticalEvents = collect($events)->where('level', 'ERROR')->count();
        $score -= min($criticalEvents * 2, 12);

        return max(40, $score);
    }
}
