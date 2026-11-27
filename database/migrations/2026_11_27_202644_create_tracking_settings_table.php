<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages du suivi, un par utilisateur : intervalle entre deux bilans et date
     * du prochain bilan (FR-42, utilisés au Lot 5).
     */
    public function up(): void
    {
        Schema::create('tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('review_interval_days')->default(21);
            $table->date('next_review_on')->nullable();
            $table->timestamps();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_settings');
    }
};
