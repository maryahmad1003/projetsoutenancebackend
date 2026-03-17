<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carnet_vaccination_id')->constrained('carnets_vaccination')->onDelete('cascade');
            $table->foreignId('medecin_id')->nullable()->constrained('medecins')->onDelete('set null');
            $table->string('nom');
            $table->date('date_administration');
            $table->date('date_rappel')->nullable();
            $table->string('lot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccins');
    }
};
