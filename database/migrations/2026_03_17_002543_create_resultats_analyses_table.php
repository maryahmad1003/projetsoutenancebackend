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
        Schema::create('resultats_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_analyse_id')->constrained('demandes_analyses')->onDelete('cascade');
            $table->foreignId('dossier_medical_id')->constrained('dossiers_medicaux')->onDelete('cascade');
            $table->foreignId('laborantin_id')->constrained()->onDelete('cascade');
            $table->string('type_analyse');
            $table->date('date_prelevement')->nullable();
            $table->date('date_resultat')->nullable();
            $table->text('resultats');
            $table->text('valeur_normale')->nullable();
            $table->text('interpretation')->nullable();
            $table->string('fichier_joint')->nullable();
            $table->enum('statut', ['en_attente', 'disponible', 'consulte'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultats_analyses');
    }
};
