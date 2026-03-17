<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'type', 'message', 'canal', 'date_envoi', 'est_lue'];
    protected $casts = ['date_envoi' => 'datetime', 'est_lue' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
}