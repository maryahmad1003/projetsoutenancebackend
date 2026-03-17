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
        'glycemie', 'type_consultation'
    ];

    protected $casts = [
        'date' => 'datetime',
        'poids' => 'float',
        'temperature' => 'float',
        'glycemie' => 'float',
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