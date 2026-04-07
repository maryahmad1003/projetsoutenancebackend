<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class OtpService
{
    protected int $ttl = 300;
    protected int $maxAttempts = 5;

    public function generate(string $telephone): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $key = $this->cacheKey($telephone);
        Cache::put($key, [
            'code'      => $code,
            'attempts'  => 0,
            'telephone' => $telephone,
        ], $this->ttl);

        $this->envoyerSms($telephone, $code);

        return $code;
    }

    public function verify(string $telephone, string $code): bool
    {
        $key  = $this->cacheKey($telephone);
        $data = Cache::get($key);

        if (!$data) {
            throw new \Exception('Code OTP expire ou inexistant. Demandez un nouveau code.');
        }

        if ($data['attempts'] >= $this->maxAttempts) {
            Cache::forget($key);
            throw new \Exception('Trop de tentatives. Demandez un nouveau code OTP.');
        }

        if ($data['code'] !== $code) {
            $data['attempts']++;
            Cache::put($key, $data, $this->ttl);
            throw new \Exception('Code OTP incorrect. ' . ($this->maxAttempts - $data['attempts']) . ' tentative(s) restante(s).');
        }

        Cache::forget($key);
        return true;
    }

    public function exists(string $telephone): bool
    {
        return Cache::has($this->cacheKey($telephone));
    }

    public function invalidate(string $telephone): void
    {
        Cache::forget($this->cacheKey($telephone));
    }

    protected function cacheKey(string $telephone): string
    {
        return 'otp:' . preg_replace('/\D/', '', $telephone);
    }

    private function envoyerSms(string $telephone, string $code): void
    {
        $numero = $this->formaterNumero($telephone);
        $message = "DocSecur: Votre code de connexion est {$code}. Ce code expire dans 5 minutes.";

        if (app()->environment('local')) {
            Log::info("[OTP SMS - LOCAL] A {$telephone} ({$numero}): {$code}");
            Log::info("[OTP SMS - LOCAL] Message: {$message}");
            return;
        }

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning("[OTP TWILIO] Config manquante, fallback log pour {$telephone}: {$code}");
            Log::info("[OTP SMS] A {$telephone}: {$code}");
            return;
        }

        try {
            $client = new Client($sid, $token);
            $client->messages->create(
                $numero,
                [
                    'from' => $from,
                    'body' => $message,
                ]
            );
            Log::info("[OTP SMS - TWILIO] Envoye a {$numero}");
        } catch (\Exception $e) {
            Log::error("[OTP SMS - ERREUR] {$e->getMessage()}, fallback log pour {$telephone}: {$code}");
            Log::info("[OTP SMS] A {$telephone}: {$code}");
        }
    }

    private function formaterNumero(string $telephone): string
    {
        $chiffres = preg_replace('/\D/', '', $telephone);

        if (str_starts_with($chiffres, '221')) {
            return '+' . $chiffres;
        }

        if (str_starts_with($chiffres, '0') && strlen($chiffres) === 9) {
            return '+221' . substr($chiffres, 1);
        }

        if (strlen($chiffres) === 9) {
            return '+221' . $chiffres;
        }

        if (strlen($chiffres) === 12 && !str_starts_with($chiffres, '221')) {
            return '+' . $chiffres;
        }

        return '+' . $chiffres;
    }
}
