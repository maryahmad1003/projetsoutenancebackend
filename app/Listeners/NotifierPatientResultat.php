<?php

namespace App\Listeners;

use App\Events\ResultatDisponible;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener : notifier le patient quand son résultat d'analyse est disponible.
 * Enregistrer dans EventServiceProvider :
 *   ResultatDisponible::class => [NotifierPatientResultat::class],
 */
class NotifierPatientResultat implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService) {}

    public function handle(ResultatDisponible $event): void
    {
        $resultat = $event->resultat;

        try {
            $patient = $resultat->dossierMedical->patient->user ?? null;
            if (!$patient) return;

            $type = $resultat->type_analyse ?? 'Analyse';
            $this->notificationService->notifierResultatDisponible($patient, $type);

            Log::info("NotifierPatientResultat: notification envoyée à user #{$patient->id}");
        } catch (\Exception $e) {
            Log::error('NotifierPatientResultat: ' . $e->getMessage());
        }
    }
}
