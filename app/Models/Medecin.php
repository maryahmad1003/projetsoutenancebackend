<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medecin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'centre_sante_id', 'matricule', 'specialite', 'num_ordre'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function centreSante()
    {
        return $this->belongsTo(CentreSante::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class);
    }

    public function teleconsultations()
    {
        return $this->hasMany(Teleconsultation::class);
    }

    public function demandesAnalyses()
    {
        return $this->hasMany(DemandeAnalyse::class);
    }
}