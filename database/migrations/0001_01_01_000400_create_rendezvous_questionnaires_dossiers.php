<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('patient_id')->constrained('patients');
            $t->foreignUuid('medecin_id')->constrained('medecins');
            $t->dateTime('date_heure');
            $t->enum('statut', ['CONFIRME', 'ANNULE', 'HONORE', 'NO_SHOW'])->default('CONFIRME');
            $t->string('motif')->nullable();
            $t->boolean('rappel_envoye')->default(false);
            $t->timestamps();
            $t->unique(['medecin_id', 'date_heure']);   // garde-fou anti-doublon en base
        });
        Schema::create('questionnaires', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('patient_id')->nullable()->constrained('patients');
            $t->string('specialite_resultat');
            $t->unsignedTinyInteger('niveau_urgence');
            $t->boolean('urgence_detectee')->default(false);
            $t->json('etapes');                          // les 5 réponses (F1)
            $t->timestamps();
        });
        Schema::create('dossiers_patients', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('patient_id')->unique()->constrained('patients');
            $t->text('antecedents')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('dossiers_patients');
        Schema::dropIfExists('questionnaires');
        Schema::dropIfExists('rendez_vous');
    }
};
