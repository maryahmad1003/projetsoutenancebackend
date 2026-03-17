<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratoire extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'adresse', 'telephone', 'types_analyses'];
    protected $casts = ['types_analyses' => 'array'];

    public function laborantins() { return $this->hasMany(Laborantin::class); }
    public function demandesAnalyses() { return $this->hasMany(DemandeAnalyse::class); }
}