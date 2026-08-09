<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retrait du dossier médical partagé.
 *
 * Le périmètre du projet a été resserré : la plateforme oriente et réserve,
 * elle ne tient plus de dossier médical consultable par le médecin ni par
 * l'administration. Les tables correspondantes sont donc déposées.
 *
 * Les renseignements de santé que le patient saisit pour lui-même — groupe
 * sanguin et allergies — restent sur la table « patients », et les allergies
 * demeurent chiffrées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('dossiers_patients');
    }

    /**
     * Le retour en arrière rétablit la structure des deux tables, sans leur
     * contenu : les données chiffrées supprimées ne sont pas récupérables.
     */
    public function down(): void
    {
        Schema::create('dossiers_patients', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $t->text('antecedents')->nullable();
            $t->timestamps();
        });

        Schema::create('consultations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('rendez_vous_id')->constrained('rendez_vous')->cascadeOnDelete();
            $t->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $t->foreignUuid('medecin_id')->constrained('medecins')->cascadeOnDelete();
            $t->text('observations')->nullable();
            $t->text('prescription')->nullable();
            $t->timestamps();
        });
    }
};
