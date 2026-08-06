<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('nom');
            $t->string('prenom');
            $t->string('email')->unique();
            // Confirmation de l'adresse : renseignée quand l'utilisateur a cliqué
            // le lien reçu par e-mail. Tant qu'elle est nulle, l'accès à l'espace
            // personnel est refusé (middleware "verified").
            $t->timestamp('email_verified_at')->nullable();
            $t->string('telephone')->nullable();
            $t->string('password');
            $t->enum('role', ['patient', 'medecin', 'admin'])->default('patient');
            $t->boolean('actif')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });
        // Jetons de réinitialisation de mot de passe (« mot de passe oublié ») :
        // un jeton haché, valable une heure, envoyé par e-mail.
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignUuid('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
        // cache / jobs restent ceux du squelette Laravel standard
        // (cf. README « copier les dossiers de CE paquet par-dessus le projet ») —
        // les recréer ici entrerait en collision avec les migrations stock déjà présentes.
    }
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
