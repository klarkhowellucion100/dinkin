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
        // 1. Matches table:
        // Frequently ordered/filtered by tournament_id + is_finalized, tournament_id + team_category + round_no + match_no
        Schema::table('matches', function (Blueprint $table) {
            $table->index(['tournament_id', 'is_finalized'], 'matches_tourna_finalized_idx');
            $table->index(['tournament_id', 'team_category', 'round_no', 'match_no'], 'matches_tourna_cat_round_match_idx');
        });

        // 2. Playoff Matches table:
        // Frequently filtered by tournament_id + is_finalized
        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->index(['tournament_id', 'is_finalized'], 'playoff_tourna_finalized_idx');
        });

        // 3. Teams table:
        // Frequently filtered/ordered by tournament_id + team_no, and tournament_id + team_category + team_bracket + team_no
        Schema::table('teams', function (Blueprint $table) {
            $table->index(['tournament_id', 'team_no'], 'teams_tourna_team_no_idx');
        });

        // 4. Open Play Sessions table:
        // Filtered & sorted on index page: WHERE user_id = ? ORDER BY session_date DESC, id DESC
        if (Schema::hasTable('open_play_sessions')) {
            Schema::table('open_play_sessions', function (Blueprint $table) {
                $table->index(['user_id', 'session_date', 'id'], 'open_play_sessions_user_date_id_idx');
            });
        }

        // 5. Open Play Matches table:
        // Foreign keys for players participating in matches (team1_player1_id, etc.)
        if (Schema::hasTable('open_play_matches')) {
            Schema::table('open_play_matches', function (Blueprint $table) {
                $table->index('team1_player1_id', 'open_play_matches_t1p1_idx');
                $table->index('team1_player2_id', 'open_play_matches_t1p2_idx');
                $table->index('team2_player1_id', 'open_play_matches_t2p1_idx');
                $table->index('team2_player2_id', 'open_play_matches_t2p2_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('open_play_matches')) {
            Schema::table('open_play_matches', function (Blueprint $table) {
                $table->dropIndex('open_play_matches_t1p1_idx');
                $table->dropIndex('open_play_matches_t1p2_idx');
                $table->dropIndex('open_play_matches_t2p1_idx');
                $table->dropIndex('open_play_matches_t2p2_idx');
            });
        }

        if (Schema::hasTable('open_play_sessions')) {
            Schema::table('open_play_sessions', function (Blueprint $table) {
                $table->dropIndex('open_play_sessions_user_date_id_idx');
            });
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('teams_tourna_team_no_idx');
        });

        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->dropIndex('playoff_tourna_finalized_idx');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_tourna_finalized_idx');
            $table->dropIndex('matches_tourna_cat_round_match_idx');
        });
    }
};
