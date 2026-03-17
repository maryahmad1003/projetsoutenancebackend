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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['rappel_rdv', 'resultat_dispo', 'prescription', 'campagne', 'suivi', 'medicament_pret']);
            $table->text('message');
            $table->enum('canal', ['sms', 'email', 'push'])->default('sms');
            $table->dateTime('date_envoi')->nullable();
            $table->boolean('est_lue')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
