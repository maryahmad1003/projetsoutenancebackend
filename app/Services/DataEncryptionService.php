<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service de chiffrement AES-256-CBC pour les données médicales sensibles.
 *
 * Utilisé pour chiffrer : antécédents, allergies, notes cliniques,
 * diagnostics, et tout champ marqué comme sensible dans les modèles.
 *
 * La clé de chiffrement est dérivée de APP_ENCRYPTION_KEY dans .env
 * (distincte de APP_KEY utilisée par Laravel).
 */
class DataEncryptionService
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        $envKey = config('app.encryption_key') ?? env('APP_ENCRYPTION_KEY');

        if (!$envKey) {
            // Fallback sur APP_KEY (base64 encoded par Laravel)
            $appKey = config('app.key');
            $this->key = base64_decode(str_replace('base64:', '', $appKey));
        } else {
            // Dériver une clé 32 bytes depuis la clé fournie
            $this->key = hash('sha256', $envKey, true);
        }
    }

    /**
     * Chiffrer une valeur sensible.
     * Retourne une chaîne base64 : IV + payload chiffré.
     */
    public function chiffrer(?string $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return $valeur;
        }

        try {
            $iv        = random_bytes(openssl_cipher_iv_length($this->cipher));
            $chiffre   = openssl_encrypt($valeur, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

            if ($chiffre === false) {
                throw new \RuntimeException('Échec du chiffrement OpenSSL');
            }

            // Stocker : base64(IV) . '.' . base64(chiffré)
            return base64_encode($iv) . '.' . base64_encode($chiffre);
        } catch (\Exception $e) {
            Log::error('DataEncryptionService::chiffrer — ' . $e->getMessage());
            return $valeur; // Ne pas bloquer le flux en cas d'erreur
        }
    }

    /**
     * Déchiffrer une valeur précédemment chiffrée.
     */
    public function dechiffrer(?string $valeurChiffree): ?string
    {
        if ($valeurChiffree === null || $valeurChiffree === '') {
            return $valeurChiffree;
        }

        // Si la valeur ne contient pas le séparateur, elle n'est pas chiffrée
        if (!str_contains($valeurChiffree, '.')) {
            return $valeurChiffree;
        }

        try {
            [$ivBase64, $payloadBase64] = explode('.', $valeurChiffree, 2);

            $iv      = base64_decode($ivBase64);
            $payload = base64_decode($payloadBase64);

            $clair = openssl_decrypt($payload, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

            if ($clair === false) {
                throw new \RuntimeException('Échec du déchiffrement OpenSSL');
            }

            return $clair;
        } catch (\Exception $e) {
            Log::error('DataEncryptionService::dechiffrer — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Chiffrer un tableau de champs sensibles dans un array de données.
     *
     * @param array $data     Données brutes
     * @param array $champs   Noms des champs à chiffrer
     */
    public function chiffrerChamps(array $data, array $champs): array
    {
        foreach ($champs as $champ) {
            if (array_key_exists($champ, $data) && $data[$champ] !== null) {
                $data[$champ] = $this->chiffrer((string) $data[$champ]);
            }
        }
        return $data;
    }

    /**
     * Déchiffrer un tableau de champs sensibles dans un array de données.
     */
    public function dechiffrerChamps(array $data, array $champs): array
    {
        foreach ($champs as $champ) {
            if (array_key_exists($champ, $data) && $data[$champ] !== null) {
                $data[$champ] = $this->dechiffrer($data[$champ]);
            }
        }
        return $data;
    }

    /**
     * Champs sensibles du dossier médical à chiffrer.
     */
    public static function champsDossierMedical(): array
    {
        return ['antecedents', 'allergies', 'notes_generales'];
    }

    /**
     * Champs sensibles d'une consultation à chiffrer.
     */
    public static function champsConsultation(): array
    {
        return [
            'diagnostic', 'notes', 'examen_clinique',
            'antecedents_signales', 'allergies_signalees',
            'traitement_en_cours', 'observations_grossesse', 'recommandations',
        ];
    }

    /**
     * Vérifier si une valeur est déjà chiffrée (heuristique).
     */
    public function estChiffre(string $valeur): bool
    {
        if (!str_contains($valeur, '.')) return false;

        [$ivPart] = explode('.', $valeur, 2);
        $decoded  = base64_decode($ivPart, true);

        return $decoded !== false
            && strlen($decoded) === openssl_cipher_iv_length($this->cipher);
    }
}
