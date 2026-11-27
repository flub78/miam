<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil de l'utilisateur (PRD §6, FR-3) : un par utilisateur.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birthdate');
            $table->string('sex', 16)->nullable();
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('start_weight_kg', 5, 1)->nullable();
            $table->date('start_date')->nullable();
            $table->string('activity_level', 16)->nullable();
            $table->text('medical_constraints')->nullable();
            $table->text('allergies')->nullable();
            $table->text('food_preferences')->nullable();
            $table->timestamps();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
