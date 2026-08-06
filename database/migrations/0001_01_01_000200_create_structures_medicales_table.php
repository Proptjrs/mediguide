<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('structures_medicales', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('nom');
            $t->string('adresse');
            $t->decimal('latitude', 10, 7);       // géocodées via Nominatim (F3)
            $t->decimal('longitude', 10, 7);
            $t->string('telephone')->nullable();
            $t->enum('type', ['hopital', 'centre_sante', 'poste_sante']);
            $t->boolean('urgences_24h')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('structures_medicales'); }
};
