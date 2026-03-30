<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'num_dossier', 'date_naissance', 'sexe',
        'adresse', 'groupe_sanguin', 'personne_contact', 'tel_contact', 'qr_code',
        'taille', 'poids', 'profession', 'situation_matrimoniale', 'nombre_enfants',
        'antecedents_medicaux', 'antecedents_chirurgicaux', 'antecedents_familiaux',
        'allergies', 'traitement_en_cours', 'assurance', 'numero_assurance'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'taille' => 'float',
        'poids' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dossierMedical()
    {
        return $this->hasOne(DossierMedical::class);
    }

    public function carnetVaccination()
    {
        return $this->hasOne(CarnetVaccination::class);
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

    public function getAgeAttribute()
    {
        return $this->date_naissance ? $this->date_naissance->age : null;
    }
}