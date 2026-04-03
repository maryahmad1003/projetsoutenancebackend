<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Durée de validité du code OTP (secondes = 5 minutes).
     */
    protected int $ttl = 300;

    /**
     * Nombre maximum de tentatives avant blocage.
     */
    protected int $maxAttempts = 5;

    /**
     * Génère un OTP à 6 chiffres, le stocke en cache et le retourne.
     */
    public function generate(string $telephone): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $key = $this->cacheKey($telephone);
        Cache::put($key, [
            'code'      => $code,
            'attempts'  => 0,
            'telephone' => $telephone,
        ], $this->ttl);

        // En production : envoyer via SMS (Twilio / Orange API)
        // Pour la soutenance : log le code
        Log::info("DocSecur OTP pour {$telephone} : {$code}");

        return $code;
    }

    /**
     * Vérifie le code OTP soumis par l'utilisateur.
     *
     * @throws \Exception si le code est expiré, bloqué ou incorrect
     */
    public function verify(string $telephone, string $code): bool
    {
        $key  = $this->cacheKey($telephone);
        $data = Cache::get($key);

        if (!$data) {
            throw new \Exception('Code OTP expiré ou inexistant. Demandez un nouveau code.');
        }

        if ($data['attempts'] >= $this->maxAttempts) {
            Cache::forget($key);
            throw new \Exception('Trop de tentatives. Demandez un nouveau code OTP.');
        }

        if ($data['code'] !== $code) {
            // Incrémenter le compteur de tentatives
            $data['attempts']++;
            Cache::put($key, $data, $this->ttl);
            throw new \Exception('Code OTP incorrect. ' . ($this->maxAttempts - $data['attempts']) . ' tentative(s) restante(s).');
        }

        // Code valide → supprimer du cache
        Cache::forget($key);
        return true;
    }

    /**
     * Vérifie si un OTP existe déjà en cache pour ce numéro
     * (évite le spam de génération).
     */
    public function exists(string $telephone): bool
    {
        return Cache::has($this->cacheKey($telephone));
    }

    /**
     * Supprime un OTP du cache (ex: en cas d'annulation).
     */
    public function invalidate(string $telephone): void
    {
        Cache::forget($this->cacheKey($telephone));
    }

    /**
     * Clé de cache unique par numéro de téléphone.
     */
    protected function cacheKey(string $telephone): string
    {
        return 'otp:' . preg_replace('/\D/', '', $telephone);
    }
}
