<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarnetVaccination extends Model
{
    use HasFactory;
    protected $table = 'carnets_vaccination';
    protected $fillable = ['patient_id'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function vaccins() { return $this->hasMany(Vaccin::class); }
}