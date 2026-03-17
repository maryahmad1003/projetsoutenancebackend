<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'dosage', 'forme'];

    public function prescriptions()
    {
        return $this->belongsToMany(Prescription::class, 'prescription_medicament')
            ->withPivot('posologie', 'duree_traitement', 'quantite')
            ->withTimestamps();
    }
}