<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('destinataire_id')->constrained('users')->onDelete('cascade');
            $table->text('contenu');
            $table->string('type', 20)->default('texte'); // texte, fichier, image
            $table->string('fichier_url')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->index(['expediteur_id', 'destinataire_id']);
            $table->index(['destinataire_id', 'lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
