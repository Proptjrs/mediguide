<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('specialites', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('nom')->unique();          // 18 spécialités (chap. 4.2.3)
            $t->text('description')->nullable();
            $t->json('mots_cles')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('specialites'); }
};
