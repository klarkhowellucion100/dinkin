<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_formats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('team_category');
            $table->string('team_bracket');
            $table->string('format')->default('round_robin');
            $table->timestamps();

            $table->unique(['tournament_id', 'team_category', 'team_bracket'], 'tournament_formats_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_formats');
    }
};
