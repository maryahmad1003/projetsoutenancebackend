<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultatAnalyse extends Model
{
    use HasFactory;
    protected $table = 'resultats_analyses';

    protected $fillable = [
        'demande_analyse_id', 'dossier_medical_id', 'laborantin_id',
        'type_analyse', 'date_prelevement', 'date_resultat', 'resultats',
        'valeur_normale', 'interpretation', 'fichier_joint', 'statut'
    ];

    protected $casts = [
        'date_prelevement' => 'date',
        'date_resultat' => 'date',
    ];

    public function demandeAnalyse()
    {
        return $this->belongsTo(DemandeAnalyse::class);
    }

    public function dossierMedical()
    {
        return $this->belongsTo(DossierMedical::class);
    }

    public function laborantin()
    {
        return $this->belongsTo(Laborantin::class);
    }
}