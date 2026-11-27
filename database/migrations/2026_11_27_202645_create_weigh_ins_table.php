<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pesées (FR-11, FR-12) ; plusieurs pesées un même jour sont permises.
     */
    public function up(): void
    {
        Schema::create('weigh_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('weight_kg', 5, 1);
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->decimal('muscle_mass_pct', 4, 1)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['user_id', 'measured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weigh_ins');
    }
};
