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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_medical_id')->constrained('dossiers_medicaux')->onDelete('cascade');
            $table->foreignId('medecin_id')->constrained()->onDelete('cascade');
            $table->dateTime('date');
            $table->string('motif');
            $table->text('diagnostic')->nullable();
            $table->text('notes')->nullable();
            $table->string('tension')->nullable();
            $table->float('poids')->nullable();
            $table->float('temperature')->nullable();
            $table->integer('frequence_cardiaque')->nullable();
            $table->float('glycemie')->nullable();
            $table->enum('type_consultation', ['presentiel', 'teleconsultation'])->default('presentiel');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
