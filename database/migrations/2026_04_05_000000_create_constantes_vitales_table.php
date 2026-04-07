<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constantes_vitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->float('valeur');
            $table->string('unite', 20);
            $table->string('source', 50)->default('manuel');
            $table->string('device_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('mesure_at');
            $table->timestamps();

            $table->index(['patient_id', 'type', 'mesure_at']);
            $table->index(['patient_id', 'mesure_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constantes_vitales');
    }
};
