<?php
namespace App\Models;

use App\Services\DataEncryptionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DossierMedical extends Model
{
    use HasFactory;

    protected $table = 'dossiers_medicaux';

    protected $fillable = [
        'patient_id', 'numero_dossier', 'antecedents',
        'allergies', 'notes_generales', 'est_archive'
    ];

    protected $casts = [
        'est_archive' => 'boolean',
    ];

    public function setAntecedentsAttribute($value): void
    {
        $this->attributes['antecedents'] = $this->encryptSensitiveValue($value);
    }

    public function getAntecedentsAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setAllergiesAttribute($value): void
    {
        $this->attributes['allergies'] = $this->encryptSensitiveValue($value);
    }

    public function getAllergiesAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setNotesGeneralesAttribute($value): void
    {
        $this->attributes['notes_generales'] = $this->encryptSensitiveValue($value);
    }

    public function getNotesGeneralesAttribute($value): mixed
    {
        return $this->decryptSensitiveValue($value);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function resultatsAnalyses()
    {
        return $this->hasMany(ResultatAnalyse::class);
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
