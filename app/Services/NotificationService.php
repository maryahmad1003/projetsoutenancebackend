<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\RendezVous;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    // Canaux disponibles
    const CANAL_INTERNE = 'interne';
    const CANAL_SMS     = 'sms';
    const CANAL_EMAIL   = 'email';

    // Types de notifications
    const TYPE_RENDEZ_VOUS  = 'rendez_vous';
    const TYPE_PRESCRIPTION = 'prescription';
    const TYPE_RESULTAT     = 'resultat';
    const TYPE_ALERTE       = 'alerte';
    const TYPE_INFO         = 'info';

    /**
     * Envoyer une notification à un utilisateur.
     */
    public function envoyer(User $user, string $type, string $message, string $canal = self::CANAL_INTERNE): Notification
    {
        $notification = Notification::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'message'    => $message,
            'canal'      => $canal,
            'date_envoi' => now(),
            'est_lue'    => false,
        ]);

        // Dispatch selon le canal
        if ($canal === self::CANAL_SMS) {
            $this->envoyerSms($user, $message);
        } elseif ($canal === self::CANAL_EMAIL) {
            $this->envoyerEmail($user, $type, $message);
        }

        return $notification;
    }

    /**
     * Notifier le patient d'une confirmation de rendez-vous.
     */
    public function notifierConfirmationRdv(RendezVous $rdv): void
    {
        $patient = $rdv->patient->user ?? null;
        $medecin = $rdv->medecin->user ?? null;

        if (!$patient) return;

        $date = $rdv->date_heure
            ? \Carbon\Carbon::parse($rdv->date_heure)->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm')
            : '—';

        $nomMedecin = $medecin ? "Dr. {$medecin->nom} {$medecin->prenom}" : 'votre médecin';

        $this->envoyer(
            $patient,
            self::TYPE_RENDEZ_VOUS,
            "Votre rendez-vous avec {$nomMedecin} le {$date} a été confirmé.",
            self::CANAL_INTERNE
        );

        // SMS si numéro disponible
        if ($patient->telephone) {
            $this->envoyer(
                $patient,
                self::TYPE_RENDEZ_VOUS,
                "DocSecur: RDV confirmé avec {$nomMedecin} le {$date}.",
                self::CANAL_SMS
            );
        }
    }

    /**
     * Notifier le patient qu'une nouvelle ordonnance est disponible.
     */
    public function notifierNouvelleOrdonnance(User $patient, string $numeroPrescription): void
    {
        $this->envoyer(
            $patient,
            self::TYPE_PRESCRIPTION,
            "Votre ordonnance n° {$numeroPrescription} est disponible. Consultez vos prescriptions.",
            self::CANAL_INTERNE
        );
    }

    /**
     * Notifier le patient qu'un résultat d'analyse est disponible.
     */
    public function notifierResultatDisponible(User $patient, string $typeAnalyse): void
    {
        $this->envoyer(
            $patient,
            self::TYPE_RESULTAT,
            "Vos résultats d'analyse ({$typeAnalyse}) sont disponibles. Consultez votre espace patient.",
            self::CANAL_INTERNE
        );
    }

    /**
     * Envoyer un rappel de rendez-vous (J-1).
     */
    public function envoyerRappelRdv(RendezVous $rdv): void
    {
        $patient    = $rdv->patient->user ?? null;
        $medecin    = $rdv->medecin->user ?? null;
        if (!$patient) return;

        $heure      = $rdv->date_heure
            ? \Carbon\Carbon::parse($rdv->date_heure)->format('H\hi')
            : '—';
        $nomMedecin = $medecin ? "Dr. {$medecin->nom}" : 'votre médecin';

        $this->envoyer(
            $patient,
            self::TYPE_RENDEZ_VOUS,
            "Rappel : vous avez un rendez-vous demain à {$heure} avec {$nomMedecin}.",
            self::CANAL_INTERNE
        );
    }

    /**
     * Récupérer les notifications non lues d'un utilisateur.
     */
    public function nonLues(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->where('est_lue', false)
            ->orderByDesc('date_envoi')
            ->get();
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues.
     */
    public function toutMarquerLu(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('est_lue', false)
            ->update(['est_lue' => true]);
    }

    /**
     * Marquer une notification spécifique comme lue.
     */
    public function marquerLue(Notification $notification): Notification
    {
        $notification->update(['est_lue' => true]);
        return $notification;
    }

    /**
     * Compter les non-lues.
     */
    public function compteurNonLues(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('est_lue', false)
            ->count();
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées — intégrations externes
    // ─────────────────────────────────────────────────────────────

    private function envoyerSms(User $user, string $message): void
    {
        // Intégration Twilio (nécessite les clés dans .env)
        // TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM
        if (!config('services.twilio.sid') || !$user->telephone) {
            return;
        }

        try {
            $client = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
            $client->messages->create(
                $user->telephone,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            Log::warning("DocSecur SMS failed for user {$user->id}: " . $e->getMessage());
        }
    }

    private function envoyerEmail(User $user, string $type, string $message): void
    {
        // Utilise le système de mail Laravel (SendGrid, Mailgun, SMTP…)
        if (!$user->email) return;

        try {
            \Illuminate\Support\Facades\Mail::raw(
                $message,
                function ($mail) use ($user, $type) {
                    $mail->to($user->email, "{$user->prenom} {$user->nom}")
                         ->subject($this->sujetEmail($type));
                }
            );
        } catch (\Exception $e) {
            Log::warning("DocSecur email failed for user {$user->id}: " . $e->getMessage());
        }
    }

    private function sujetEmail(string $type): string
    {
        return match ($type) {
            self::TYPE_RENDEZ_VOUS  => '[DocSecur] Votre rendez-vous',
            self::TYPE_PRESCRIPTION => '[DocSecur] Nouvelle ordonnance disponible',
            self::TYPE_RESULTAT     => '[DocSecur] Résultats d\'analyse disponibles',
            self::TYPE_ALERTE       => '[DocSecur] Alerte médicale',
            default                 => '[DocSecur] Notification',
        };
    }
}
