<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'message'     => $this->message,
            'canal'       => $this->canal,
            'est_lue'     => $this->est_lue,
            'date_envoi'  => $this->date_envoi,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
