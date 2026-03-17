<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Administrateur;
use App\Models\Pharmacien;
use App\Models\Laborantin;
use App\Models\Notification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'role',
        'langue',
        'photo_profil',
        'est_actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'est_actif' => 'boolean',
    ];


    public function medecin()
    {
        return $this->hasOne(Medecin::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class);
    }

    public function pharmacien()
    {
        return $this->hasOne(Pharmacien::class);
    }

    public function laborantin()
    {
        return $this->hasOne(Laborantin::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function isMedecin()
    {
        return $this->role === 'medecin';
    }

    public function isPatient()
    {
        return $this->role === 'patient';
    }

    public function isAdmin()
    {
        return $this->role === 'administrateur';
    }

    public function isPharmacien()
    {
        return $this->role === 'pharmacien';
    }

    public function isLaborantin()
    {
        return $this->role === 'laborantin';
    }
}