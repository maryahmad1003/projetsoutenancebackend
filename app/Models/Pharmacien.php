<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacien extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'pharmacie_id', 'num_licence'];

    public function user() { return $this->belongsTo(User::class); }
    public function pharmacie() { return $this->belongsTo(Pharmacie::class); }
}