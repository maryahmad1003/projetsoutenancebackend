<?php

namespace App\Events;

use App\Models\ResultatAnalyse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché quand un laborantin publie un résultat d'analyse.
 */
class ResultatDisponible
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ResultatAnalyse $resultat) {}
}
