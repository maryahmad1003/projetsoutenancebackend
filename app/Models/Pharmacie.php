<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacie extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'adresse', 'telephone', 'horaires'];

    public function pharmaciens() { return $this->hasMany(Pharmacien::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
}