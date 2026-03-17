<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id', 'medecin_id', 'numero', 'date_emission',
        'date_expiration', 'statut', 'notes', 'pharmacie_id'
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_expiration' => 'date',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class);
    }

    public function medicaments()
    {
        return $this->belongsToMany(Medicament::class, 'prescription_medicament')
            ->withPivot('posologie', 'duree_traitement', 'quantite')
            ->withTimestamps();
    }

    public function pharmacie()
    {
        return $this->belongsTo(Pharmacie::class);
    }
}