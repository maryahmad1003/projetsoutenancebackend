<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacie extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'adresse', 'telephone', 'horaires'];

    public static function ensureDefaultExists(): self
    {
        return static::firstOrCreate(
            ['nom' => 'Pharmacie Centrale DocSecur'],
            [
                'adresse' => 'Centre de sante partenaire DocSecur, Dakar',
                'telephone' => '+221330000000',
                'horaires' => 'Lun-Sam: 08h-20h',
            ]
        );
    }

    public function pharmaciens() { return $this->hasMany(Pharmacien::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
}
