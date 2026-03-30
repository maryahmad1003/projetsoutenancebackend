<?php

namespace App\Events;

use App\Models\Prescription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché quand une ordonnance est créée/émise par le médecin.
 */
class OrdonnanceEnvoyee
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Prescription $prescription) {}
}
