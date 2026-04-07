<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstanteVitale extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'type',
        'valeur',
        'unite',
        'source',
        'device_id',
        'notes',
        'mesure_at',
    ];

    protected $casts = [
        'valeur' => 'float',
        'mesure_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public static function typesDisponibles()
    {
        return [
            'tension_systolique' => ['label' => 'Tension systolique', 'unite' => 'mmHg', 'min' => 70, 'max' => 250, 'icon' => 'heart'],
            'tension_diastolique' => ['label' => 'Tension diastolique', 'unite' => 'mmHg', 'min' => 40, 'max' => 150, 'icon' => 'heart'],
            'glycemie' => ['label' => 'Glycémie', 'unite' => 'g/L', 'min' => 0.5, 'max' => 6, 'icon' => 'droplet'],
            'frequence_cardiaque' => ['label' => 'Fréquence cardiaque', 'unite' => 'bpm', 'min' => 30, 'max' => 220, 'icon' => 'activity'],
            'temperature' => ['label' => 'Température', 'unite' => '°C', 'min' => 30, 'max' => 45, 'icon' => 'thermometer'],
            'saturation_oxygene' => ['label' => 'Saturation oxygène', 'unite' => '%', 'min' => 70, 'max' => 100, 'icon' => 'wind'],
            'poids' => ['label' => 'Poids', 'unite' => 'kg', 'min' => 1, 'max' => 300, 'icon' => 'scale'],
            'taille' => ['label' => 'Taille', 'unite' => 'cm', 'min' => 30, 'max' => 250, 'icon' => 'ruler'],
        ];
    }

    public static function detecterAnomalie($type, $valeur)
    {
        $types = self::typesDisponibles();
        if (!isset($types[$type])) {
            return ['statut' => 'normal', 'message' => 'Type inconnu'];
        }

        $config = $types[$type];
        if ($valeur < $config['min'] || $valeur > $config['max']) {
            return ['statut' => 'anomalie', 'message' => "Valeur {$type} anormale: {$valeur}"];
        }

        $alerte = match ($type) {
            'tension_systolique' => $valeur > 180 || $valeur < 90,
            'tension_diastolique' => $valeur > 120 || $valeur < 60,
            'glycemie' => $valeur > 2.5 || $valeur < 0.7,
            'frequence_cardiaque' => $valeur > 150 || $valeur < 40,
            'temperature' => $valeur > 41 || $valeur < 35,
            'saturation_oxygene' => $valeur < 90,
            default => false,
        };

        return $alerte
            ? ['statut' => 'alerte', 'message' => "Alerte {$type}: {$valeur} {$config['unite']}"]
            : ['statut' => 'normal', 'message' => 'Valeur normale'];
    }
}
