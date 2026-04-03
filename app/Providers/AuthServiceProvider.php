<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

// Modèles
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\Patient;
use App\Models\DossierMedical;

// Policies
use App\Policies\ConsultationPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\PatientPolicy;
use App\Policies\DossierMedicalPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapping Modèle → Policy.
     */
    protected $policies = [
        Consultation::class  => ConsultationPolicy::class,
        Prescription::class  => PrescriptionPolicy::class,
        Patient::class       => PatientPolicy::class,
        DossierMedical::class=> DossierMedicalPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Passport — durée de validité des tokens
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
