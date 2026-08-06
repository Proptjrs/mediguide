<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('utilisateur_id')->constrained('users')->cascadeOnDelete();
            $t->date('date_naissance')->nullable();
            $t->enum('sexe', ['F', 'M'])->nullable();
            $t->string('groupe_sanguin', 5)->nullable();
            $t->text('allergies')->nullable();
            $t->timestamps();
        });
        Schema::create('medecins', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('utilisateur_id')->constrained('users')->cascadeOnDelete();
            $t->foreignUuid('structure_id')->constrained('structures_medicales');
            $t->foreignUuid('specialite_id')->constrained('specialites');
            $t->string('num_ordre')->unique();     // vérifié par l'admin (UC-A2)
            $t->boolean('valide')->default(false);
            $t->timestamps();
        });
        Schema::create('disponibilites', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('medecin_id')->constrained('medecins')->cascadeOnDelete();
            // type BASE : plage hebdomadaire fixée par l'admin (chap. 3).
            // type INDISPONIBILITE : absence ponctuelle déclarée par le médecin (congé, mission, urgence, formation).
            $t->enum('type', ['BASE', 'INDISPONIBILITE'])->default('BASE');
            $t->tinyInteger('jour_semaine')->nullable();   // 1 = lundi … 5 = vendredi (BASE uniquement)
            $t->date('date')->nullable();                  // jour concerné (INDISPONIBILITE uniquement)
            $t->time('heure_debut')->nullable();           // null = journée entière (INDISPONIBILITE)
            $t->time('heure_fin')->nullable();
            $t->string('motif')->nullable();               // congé / mission / urgence / formation
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('disponibilites');
        Schema::dropIfExists('medecins');
        Schema::dropIfExists('patients');
    }
};
