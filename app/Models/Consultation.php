<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_medical_id', 'medecin_id', 'date', 'motif', 'diagnostic',
        'notes', 'tension', 'poids', 'temperature', 'frequence_cardiaque',
        'glycemie', 'type_consultation',
        'taille', 'imc', 'saturation_oxygene', 'frequence_respiratoire',
        'examen_clinique', 'antecedents_signales', 'allergies_signalees', 'traitement_en_cours',
        'est_enceinte', 'semaines_grossesse', 'date_derniere_regle',
        'date_accouchement_prevue', 'groupe_sanguin_grossesse', 'observations_grossesse',
        'recommandations', 'prochain_rdv', 'urgence'
    ];

    protected $casts = [
        'date' => 'datetime',
        'poids' => 'float',
        'temperature' => 'float',
        'glycemie' => 'float',
        'taille' => 'float',
        'imc' => 'float',
        'saturation_oxygene' => 'float',
        'est_enceinte' => 'boolean',
        'date_derniere_regle' => 'date',
        'date_accouchement_prevue' => 'date',
        'prochain_rdv' => 'date',
    ];

    public function dossierMedical()
    {
        return $this->belongsTo(DossierMedical::class);
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function teleconsultation()
    {
        return $this->hasOne(Teleconsultation::class);
    }
}