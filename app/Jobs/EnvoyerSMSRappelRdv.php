<?php

namespace App\Jobs;

use App\Models\RendezVous;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job : envoyer un SMS de rappel pour un rendez-vous (J-1).
 * Déclenché par le scheduler Laravel dans Console/Kernel.php :
 *   $schedule->job(new EnvoyerSMSRappelRdv)->dailyAt('08:00');
 */
class EnvoyerSMSRappelRdv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct() {}

    public function handle(NotificationService $notificationService): void
    {
        // Rendez-vous confirmés prévus demain
        $rdvsDemain = RendezVous::where('statut', 'confirme')
            ->whereDate('date_heure', now()->addDay()->toDateString())
            ->with(['patient.user', 'medecin.user'])
            ->get();

        $envoyes = 0;

        foreach ($rdvsDemain as $rdv) {
            try {
                $notificationService->envoyerRappelRdv($rdv);
                $envoyes++;
            } catch (\Exception $e) {
                Log::warning("EnvoyerSMSRappelRdv: échec pour RDV #{$rdv->id} — " . $e->getMessage());
            }
        }

        Log::info("EnvoyerSMSRappelRdv: {$envoyes} rappels envoyés sur {$rdvsDemain->count()} RDV.");
    }
}
