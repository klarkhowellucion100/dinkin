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
        Schema::create('open_play_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('court_no');
            $table->unsignedInteger('round_no');
            $table->string('status')->default('active');
            $table->foreignId('team1_player1_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('team1_player2_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('team2_player1_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('team2_player2_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedSmallInteger('team1_score')->nullable();
            $table->unsignedSmallInteger('team2_score')->nullable();
            $table->unsignedTinyInteger('winner_team')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['open_play_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_matches');
    }
};
