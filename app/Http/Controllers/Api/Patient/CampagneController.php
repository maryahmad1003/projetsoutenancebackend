<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Campagne;
use Illuminate\Http\Request;

class CampagneController extends Controller
{
    public function index(Request $request)
    {
        $query = Campagne::query()
            ->orderByDesc('date_debut')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $campagnes = $query->get()->map(function (Campagne $campagne) {
            return [
                'id' => $campagne->id,
                'titre' => $campagne->titre,
                'contenu' => $campagne->description ?: 'Cette campagne de sante est diffusee par DocSecur pour informer et sensibiliser les patients.',
                'categorie' => $this->mapCategorie($campagne->type),
                'type' => $campagne->type,
                'date' => optional($campagne->date_debut)->format('d/m/Y'),
                'date_debut' => optional($campagne->date_debut)->toDateString(),
                'date_fin' => optional($campagne->date_fin)->toDateString(),
                'region' => $campagne->region,
                'cible' => $campagne->cible,
                'auteur' => 'Administration DocSecur',
                'duree' => $this->estimateReadingTime($campagne->description),
                'tags' => array_values(array_filter([
                    $this->mapCategorie($campagne->type),
                    $campagne->region,
                    $campagne->cible,
                ])),
            ];
        });

        return response()->json($campagnes);
    }

    private function mapCategorie(?string $type): string
    {
        return match ($type) {
            'vaccination' => 'Vaccination',
            'sensibilisation' => 'Santé mentale',
            default => 'Prévention',
        };
    }

    private function estimateReadingTime(?string $content): string
    {
        $words = str_word_count(strip_tags((string) $content));
        $minutes = max(1, (int) ceil($words / 180));

        return "{$minutes} min";
    }
}
