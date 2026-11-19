<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique libre des changements d'habitudes acquis (FR-4).
     */
    public function up(): void
    {
        Schema::create('acquired_habit_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('changed_on');
            $table->string('description', 500);
            $table->timestamps();
            $table->auditColumns();

            $table->index(['user_id', 'changed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquired_habit_changes');
    }
};
