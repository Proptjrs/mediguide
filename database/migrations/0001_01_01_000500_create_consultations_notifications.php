<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $t) {   // F9 : comptes-rendus
            $t->uuid('id')->primary();
            $t->foreignUuid('rendez_vous_id')->unique()->constrained('rendez_vous');
            $t->foreignUuid('medecin_id')->constrained('medecins');
            $t->foreignUuid('patient_id')->constrained('patients');
            $t->text('observations');
            $t->text('prescription')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {  // canal database (F5)
            $t->uuid('id')->primary();
            $t->string('type');
            // uuidMorphs et non morphs : les utilisateurs ont une clé primaire UUID.
            // Avec morphs(), notifiable_id serait un entier et l'insertion échouerait
            // (SQLSTATE 22007), rendant le canal "database" totalement inopérant.
            $t->uuidMorphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('consultations');
    }
};
