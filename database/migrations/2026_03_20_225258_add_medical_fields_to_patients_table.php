<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->float('taille')->nullable()->after('qr_code');
            $table->float('poids')->nullable()->after('taille');
            $table->string('profession')->nullable()->after('poids');
            $table->string('situation_matrimoniale')->nullable()->after('profession');
            $table->integer('nombre_enfants')->nullable()->after('situation_matrimoniale');
            $table->text('antecedents_medicaux')->nullable()->after('nombre_enfants');
            $table->text('antecedents_chirurgicaux')->nullable()->after('antecedents_medicaux');
            $table->text('antecedents_familiaux')->nullable()->after('antecedents_chirurgicaux');
            $table->text('allergies')->nullable()->after('antecedents_familiaux');
            $table->text('traitement_en_cours')->nullable()->after('allergies');
            $table->string('assurance')->nullable()->after('traitement_en_cours');
            $table->string('numero_assurance')->nullable()->after('assurance');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'taille', 'poids', 'profession', 'situation_matrimoniale', 'nombre_enfants',
                'antecedents_medicaux', 'antecedents_chirurgicaux', 'antecedents_familiaux',
                'allergies', 'traitement_en_cours', 'assurance', 'numero_assurance'
            ]);
        });
    }
};