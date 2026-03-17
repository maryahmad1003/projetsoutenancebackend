<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccin extends Model
{
    use HasFactory;
    protected $fillable = ['carnet_vaccination_id', 'medecin_id', 'nom', 'date_administration', 'date_rappel', 'lot'];
    protected $casts = ['date_administration' => 'date', 'date_rappel' => 'date'];

    public function carnetVaccination() { return $this->belongsTo(CarnetVaccination::class); }
    public function medecin() { return $this->belongsTo(Medecin::class); }
}