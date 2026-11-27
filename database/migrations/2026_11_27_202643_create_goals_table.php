<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Objectifs historisés (FR-6 à FR-10) : un changement crée une nouvelle ligne,
     * l'objectif courant est celle dont `effective_from` est la plus récente (FR-9).
     */
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->decimal('target_weight_kg', 5, 1);
            $table->decimal('target_rate_kg_per_week', 3, 2);
            $table->unsignedSmallInteger('daily_calorie_budget');
            $table->unsignedSmallInteger('protein_target_g')->nullable();
            $table->date('indicative_target_date')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['user_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
