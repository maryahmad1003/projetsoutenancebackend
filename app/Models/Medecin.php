<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medecin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'centre_sante_id', 'matricule', 'specialite', 'num_ordre', 'horaires', 'disponibilites', 'accepte_rdv_en_ligne'];

    protected $casts = [
        'horaires' => 'array',
        'disponibilites' => 'array',
        'accepte_rdv_en_ligne' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function centreSante()
    {
        return $this->belongsTo(CentreSante::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class);
    }

    public function teleconsultations()
    {
        return $this->hasMany(Teleconsultation::class);
    }

    public function demandesAnalyses()
    {
        return $this->hasMany(DemandeAnalyse::class);
    }

    public function getDefaultHoraires(): array
    {
        return [
            'lundi'    => ['debut' => '08:00', 'fin' => '17:00', 'active' => true],
            'mardi'   => ['debut' => '08:00', 'fin' => '17:00', 'active' => true],
            'mercredi' => ['debut' => '08:00', 'fin' => '17:00', 'active' => true],
            'jeudi'   => ['debut' => '08:00', 'fin' => '17:00', 'active' => true],
            'vendredi' => ['debut' => '08:00', 'fin' => '17:00', 'active' => true],
            'samedi'  => ['debut' => '09:00', 'fin' => '13:00', 'active' => false],
            'dimanche' => ['active' => false],
        ];
    }

    public function isAvailableOn(string $dateHeure): bool
    {
        $date = \Carbon\Carbon::parse($dateHeure);
        $jour = strtolower($date->locale('fr')->dayName);
        
        $horaires = $this->horaires ?? $this->getDefaultHoraires();
        
        if (!isset($horaires[$jour]) || !$horaires[$jour]['active']) {
            return false;
        }

        $heure = $date->format('H:i');
        return $heure >= $horaires[$jour]['debut'] && $heure <= $horaires[$jour]['fin'];
    }

    public function getProchainCreneauxDisponibles(int $nombre = 5): array
    {
        $creneaux = [];
        $horaires = $this->horaires ?? $this->getDefaultHoraires();
        
        $date = now();
        $compteur = 0;
        
        while ($compteur < $nombre && $date->diffInDays(now()) < 30) {
            $jour = strtolower($date->locale('fr')->dayName);
            
            if (isset($horaires[$jour]) && $horaires[$jour]['active']) {
                $debut = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $horaires[$jour]['debut']);
                $fin = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $horaires[$jour]['fin']);
                
                $rdvExistants = $this->rendezVous()
                    ->whereDate('date_heure', $date->format('Y-m-d'))
                    ->whereIn('statut', ['en_attente', 'confirme'])
                    ->pluck('date_heure')
                    ->map(fn($d) => \Carbon\Carbon::parse($d)->format('H:i'))
                    ->toArray();

                while ($debut->lessThan($fin) && $compteur < $nombre) {
                    $heureCreneau = $debut->format('H:i');
                    if (!in_array($heureCreneau, $rdvExistants)) {
                        $creneaux[] = [
                            'datetime' => $debut->toIso8601String(),
                            'jour' => $jour,
                            'heure' => $heureCreneau,
                        ];
                        $compteur++;
                    }
                    $debut->addMinutes(30);
                }
            }
            
            $date->addDay();
        }
        
        return $creneaux;
    }
}