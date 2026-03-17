<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laborantin extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'laboratoire_id', 'num_agrement'];

    public function user() { return $this->belongsTo(User::class); }
    public function laboratoire() { return $this->belongsTo(Laboratoire::class); }
    public function resultatsAnalyses() { return $this->hasMany(ResultatAnalyse::class); }
}