<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\PatientRepositoryInterface;
use App\Repositories\Interfaces\ConsultationRepositoryInterface;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\Eloquent\PatientRepository;
use App\Repositories\Eloquent\ConsultationRepository;
use App\Repositories\Eloquent\MessageRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PatientRepositoryInterface::class, PatientRepository::class);
        $this->app->bind(ConsultationRepositoryInterface::class, ConsultationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
    }
}
