<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campagne extends Model
{
    use HasFactory;
    protected $fillable = ['administrateur_id', 'titre', 'description', 'date_debut', 'date_fin', 'cible', 'region', 'type'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function administrateur() { return $this->belongsTo(Administrateur::class); }
}