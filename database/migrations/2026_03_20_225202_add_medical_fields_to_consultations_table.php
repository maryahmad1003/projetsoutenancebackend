<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Constantes vitales complètes
            $table->float('taille')->nullable()->after('glycemie');
            $table->float('imc')->nullable()->after('taille');
            $table->float('saturation_oxygene')->nullable()->after('imc');
            $table->string('frequence_respiratoire')->nullable()->after('saturation_oxygene');
            
            // Examen clinique
            $table->text('examen_clinique')->nullable()->after('frequence_respiratoire');
            $table->text('antecedents_signales')->nullable()->after('examen_clinique');
            $table->text('allergies_signalees')->nullable()->after('antecedents_signales');
            $table->text('traitement_en_cours')->nullable()->after('allergies_signalees');
            
            // Grossesse
            $table->boolean('est_enceinte')->default(false)->after('traitement_en_cours');
            $table->integer('semaines_grossesse')->nullable()->after('est_enceinte');
            $table->date('date_derniere_regle')->nullable()->after('semaines_grossesse');
            $table->date('date_accouchement_prevue')->nullable()->after('date_derniere_regle');
            $table->string('groupe_sanguin_grossesse')->nullable()->after('date_accouchement_prevue');
            $table->text('observations_grossesse')->nullable()->after('groupe_sanguin_grossesse');
            
            // Suivi
            $table->text('recommandations')->nullable()->after('observations_grossesse');
            $table->date('prochain_rdv')->nullable()->after('recommandations');
            $table->enum('urgence', ['faible', 'moyenne', 'haute', 'critique'])->default('faible')->after('prochain_rdv');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'taille', 'imc', 'saturation_oxygene', 'frequence_respiratoire',
                'examen_clinique', 'antecedents_signales', 'allergies_signalees', 'traitement_en_cours',
                'est_enceinte', 'semaines_grossesse', 'date_derniere_regle', 'date_accouchement_prevue',
                'groupe_sanguin_grossesse', 'observations_grossesse',
                'recommandations', 'prochain_rdv', 'urgence'
            ]);
        });
    }
};