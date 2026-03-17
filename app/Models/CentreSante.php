<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentreSante extends Model
{
    use HasFactory;
    protected $table = 'centres_sante';
    protected $fillable = ['nom', 'adresse', 'telephone', 'type', 'region', 'coordonnees_gps'];

    public function medecins() { return $this->hasMany(Medecin::class); }
    public function tableauxBord() { return $this->hasMany(TableauDeBord::class); }
}