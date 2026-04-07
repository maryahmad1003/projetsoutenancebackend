<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medecins', function (Blueprint $table) {
            $table->json('horaires')->nullable()->after('specialite');
            $table->json('disponibilites')->nullable()->after('horaires');
            $table->boolean('accepte_rdv_en_ligne')->default(true)->after('disponibilites');
        });
    }

    public function down(): void
    {
        Schema::table('medecins', function (Blueprint $table) {
            $table->dropColumn(['horaires', 'disponibilites', 'accepte_rdv_en_ligne']);
        });
    }
};