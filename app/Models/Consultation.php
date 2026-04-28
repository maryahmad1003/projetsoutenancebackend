<?php

namespace App\Models;

use App\Services\DataEncryptionService;
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

    public function setDiagnosticAttribute($value): void
    {
        $this->attributes['diagnostic'] = $this->encryptSensitiveValue($value);
    }

    public function getDiagnosticAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['notes'] = $this->encryptSensitiveValue($value);
    }

    public function getNotesAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setExamenCliniqueAttribute($value): void
    {
        $this->attributes['examen_clinique'] = $this->encryptSensitiveValue($value);
    }

    public function getExamenCliniqueAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setAntecedentsSignalesAttribute($value): void
    {
        $this->attributes['antecedents_signales'] = $this->encryptSensitiveValue($value);
    }

    public function getAntecedentsSignalesAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setAllergiesSignaleesAttribute($value): void
    {
        $this->attributes['allergies_signalees'] = $this->encryptSensitiveValue($value);
    }

    public function getAllergiesSignaleesAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setTraitementEnCoursAttribute($value): void
    {
        $this->attributes['traitement_en_cours'] = $this->encryptSensitiveValue($value);
    }

    public function getTraitementEnCoursAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setObservationsGrossesseAttribute($value): void
    {
        $this->attributes['observations_grossesse'] = $this->encryptSensitiveValue($value);
    }

    public function getObservationsGrossesseAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setRecommandationsAttribute($value): void
    {
        $this->attributes['recommandations'] = $this->encryptSensitiveValue($value);
    }

    public function getRecommandationsAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

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

    private function encryptSensitiveValue(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $service = app(DataEncryptionService::class);

        return $service->estChiffre($value)
            ? $value
            : $service->chiffrer($value);
    }

    private function decryptSensitiveValue(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return app(DataEncryptionService::class)->dechiffrer($value) ?? $value;
    }
}
