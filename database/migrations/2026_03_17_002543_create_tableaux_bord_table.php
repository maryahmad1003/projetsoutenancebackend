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
        Schema::create('tableaux_bord', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centre_sante_id')->constrained('centres_sante')->onDelete('cascade');
            $table->dateTime('date_generation');
            $table->integer('nombre_patients')->default(0);
            $table->integer('nombre_consultations')->default(0);
            $table->json('pathologies_frequentes')->nullable();
            $table->json('indicateurs_performance')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tableaux_bord');
    }
};
