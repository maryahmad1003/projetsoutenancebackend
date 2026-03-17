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
        Schema::create('demandes_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medecin_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('laboratoire_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type_analyse');
            $table->boolean('urgence')->default(false);
            $table->text('notes')->nullable();
            $table->enum('statut', ['envoyee', 'en_cours', 'terminee'])->default('envoyee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_analyses');
    }
};
