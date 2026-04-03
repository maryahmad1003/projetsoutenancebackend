<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration corrective : rend nullable les clés étrangères et champs obligatoires
 * des tables medecins, pharmaciens et laborantins afin de permettre l'inscription
 * d'un utilisateur sans avoir encore de centre, pharmacie ou laboratoire assigné.
 * L'administrateur peut ensuite compléter ces informations via l'API admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Médecins ──────────────────────────────────────────────────────
        Schema::table('medecins', function (Blueprint $table) {
            // Supprimer la contrainte FK avant de rendre le champ nullable
            $table->dropForeign(['centre_sante_id']);
            $table->unsignedBigInteger('centre_sante_id')->nullable()->change();
            $table->foreign('centre_sante_id')->references('id')->on('centres_sante')->onDelete('set null');

            $table->string('matricule')->nullable()->change();
            $table->string('specialite')->nullable()->change();
        });

        // ── Pharmaciens ───────────────────────────────────────────────────
        Schema::table('pharmaciens', function (Blueprint $table) {
            $table->dropForeign(['pharmacie_id']);
            $table->unsignedBigInteger('pharmacie_id')->nullable()->change();
            $table->foreign('pharmacie_id')->references('id')->on('pharmacies')->onDelete('set null');

            $table->string('num_licence')->nullable()->change();
        });

        // ── Laborantins ───────────────────────────────────────────────────
        Schema::table('laborantins', function (Blueprint $table) {
            $table->dropForeign(['laboratoire_id']);
            $table->unsignedBigInteger('laboratoire_id')->nullable()->change();
            $table->foreign('laboratoire_id')->references('id')->on('laboratoires')->onDelete('set null');

            $table->string('num_agrement')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restaurer les contraintes NOT NULL (attention : échouera s'il existe des NULL en base)
        Schema::table('medecins', function (Blueprint $table) {
            $table->dropForeign(['centre_sante_id']);
            $table->unsignedBigInteger('centre_sante_id')->nullable(false)->change();
            $table->foreign('centre_sante_id')->references('id')->on('centres_sante')->onDelete('cascade');
            $table->string('matricule')->nullable(false)->change();
            $table->string('specialite')->nullable(false)->change();
        });

        Schema::table('pharmaciens', function (Blueprint $table) {
            $table->dropForeign(['pharmacie_id']);
            $table->unsignedBigInteger('pharmacie_id')->nullable(false)->change();
            $table->foreign('pharmacie_id')->references('id')->on('pharmacies')->onDelete('cascade');
            $table->string('num_licence')->nullable(false)->change();
        });

        Schema::table('laborantins', function (Blueprint $table) {
            $table->dropForeign(['laboratoire_id']);
            $table->unsignedBigInteger('laboratoire_id')->nullable(false)->change();
            $table->foreign('laboratoire_id')->references('id')->on('laboratoires')->onDelete('cascade');
            $table->string('num_agrement')->nullable(false)->change();
        });
    }
};
