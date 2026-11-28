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
        Schema::create('elimination_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('team_category');
            $table->string('team_bracket');
            $table->string('format');
            $table->string('bracket_side')->default('winners');
            $table->unsignedInteger('round_number');
            $table->unsignedInteger('match_number');
            $table->string('match_label');
            $table->foreignId('team1_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team2_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team1_source_match_id')->nullable()->constrained('elimination_matches')->nullOnDelete();
            $table->string('team1_source_result')->nullable();
            $table->foreignId('team2_source_match_id')->nullable()->constrained('elimination_matches')->nullOnDelete();
            $table->string('team2_source_result')->nullable();
            $table->unsignedBigInteger('team1_score')->nullable();
            $table->unsignedBigInteger('team2_score')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('loser_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->boolean('is_finalized')->default(false);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tournament_id', 'team_category', 'team_bracket', 'format'], 'elimination_matches_scope_idx');
            $table->index(['tournament_id', 'team_category', 'bracket_side', 'round_number'], 'elimination_matches_round_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elimination_matches');
    }
};
