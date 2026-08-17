<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deux ajouts demandés après la relecture de l'encadreur.
 *
 * 1. La secrétaire médicale. Dans un service hospitalier, ce n'est pas le médecin
 *    qui ouvre lui-même ses créneaux : sa secrétaire tient l'agenda. Le rôle
 *    existe donc dans la plateforme, avec les droits limités à cet agenda.
 * 2. L'assistant conversationnel. Chaque échange est conservé, ce qui permet à
 *    l'administration de savoir ce que les patients cherchent réellement.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La colonne était une énumération figée à trois valeurs : elle devient
        // une chaîne, seule forme qui se modifie sans peine sous MySQL comme
        // sous PostgreSQL. La validation des rôles se fait dans l'application.
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('patient')->change();
        });

        Schema::create('secretaires', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('utilisateur_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->foreignUuid('medecin_id')->constrained('medecins')->cascadeOnDelete();
            $t->timestamps();
        });

        Schema::create('echanges_assistant', function (Blueprint $t) {
            $t->uuid('id')->primary();
            // Un visiteur non identifié peut interroger l'assistant : l'auteur
            // est alors inconnu, sans que l'échange soit perdu.
            $t->foreignUuid('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('intention', 40);          // ce que la règle a reconnu
            $t->text('question');
            $t->text('reponse');
            $t->boolean('urgence_detectee')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echanges_assistant');
        Schema::dropIfExists('secretaires');
        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['patient', 'medecin', 'admin'])->default('patient')->change();
        });
    }
};
