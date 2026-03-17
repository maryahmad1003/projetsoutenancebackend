<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('medecin_id')->constrained()->onDelete('cascade');
            $table->dateTime('date_heure');
            $table->integer('duree')->default(30);
            $table->string('motif');
            $table->enum('statut', ['confirme', 'en_attente', 'annule', 'termine'])->default('en_attente');
            $table->enum('type', ['consultation', 'suivi', 'urgence', 'teleconsultation'])->default('consultation');
            $table->boolean('rappel_envoye')->default(false);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
