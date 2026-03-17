<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeAnalyse extends Model
{
    use HasFactory;
    protected $table = 'demandes_analyses';

    protected $fillable = [
        'medecin_id', 'patient_id', 'laboratoire_id',
        'type_analyse', 'urgence', 'notes', 'statut'
    ];

    protected $casts = [
        'urgence' => 'boolean',
    ];

    public function medecin()
    {
        return $this->belongsTo(Medecin::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function laboratoire()
    {
        return $this->belongsTo(Laboratoire::class);
    }

    public function resultat()
    {
        return $this->hasOne(ResultatAnalyse::class);
    }
}