<?php

namespace App\Listeners;

use App\Events\OrdonnanceEnvoyee;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener : notifier le patient quand une nouvelle ordonnance est émise.
 * Enregistrer dans EventServiceProvider :
 *   OrdonnanceEnvoyee::class => [NotifierPatientOrdonnance::class],
 */
class NotifierPatientOrdonnance implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService) {}

    public function handle(OrdonnanceEnvoyee $event): void
    {
        $prescription = $event->prescription;

        try {
            $patient = $prescription->consultation
                ->dossierMedical
                ->patient
                ->user ?? null;

            if (!$patient) return;

            $this->notificationService->notifierNouvelleOrdonnance($patient, $prescription->numero);

            Log::info("NotifierPatientOrdonnance: notification envoyée à user #{$patient->id}");
        } catch (\Exception $e) {
            Log::error('NotifierPatientOrdonnance: ' . $e->getMessage());
        }
    }
}
