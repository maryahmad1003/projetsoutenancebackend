<?php
namespace App\Models;

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
}