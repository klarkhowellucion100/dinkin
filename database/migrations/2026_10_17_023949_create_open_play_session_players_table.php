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
        if (Schema::hasTable('open_play_session_players')) {
            if (! Schema::hasIndex('open_play_session_players', 'session_players_queue_idx')) {
                Schema::table('open_play_session_players', function (Blueprint $table): void {
                    $table->index(['open_play_session_id', 'queue_position'], 'session_players_queue_idx');
                });
            }

            return;
        }

        Schema::create('open_play_session_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('queue_position')->default(1);
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->foreignId('fixed_partner_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->unique(['open_play_session_id', 'player_id']);
            $table->index(['open_play_session_id', 'queue_position'], 'session_players_queue_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_session_players');
    }
};
