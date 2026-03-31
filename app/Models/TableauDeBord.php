<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableauDeBord extends Model
{
    use HasFactory;
    protected $table = 'tableaux_bord';
    protected $fillable = ['centre_sante_id', 'date_generation', 'nombre_patients', 'nombre_consultations', 'pathologies_frequentes', 'indicateurs_performance'];
    protected $casts = ['date_generation' => 'datetime', 'pathologies_frequentes' => 'array', 'indicateurs_performance' => 'array'];

    public function centreSante() { return $this->belongsTo(CentreSante::class); }
}