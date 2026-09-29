<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['approval', 'name'], 'users_approval_name_idx');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->index('created_at', 'tournaments_created_at_idx');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->change();

            $table->index(['tournament_id', 'team_category', 'team_bracket'], 'teams_tournament_category_bracket_idx');
            $table->index(['user_id', 'tournament_id'], 'teams_user_tournament_idx');

            $table->foreign('tournament_id', 'teams_tournament_fk')
                ->references('id')->on('tournaments')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'teams_user_fk')
                ->references('id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'tournaments_user_date_idx');
            $table->foreign('user_id', 'tournaments_user_fk')
                ->references('id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->change();
            $table->unsignedBigInteger('team1_id')->change();
            $table->unsignedBigInteger('team2_id')->change();
            $table->unsignedBigInteger('winner_id')->nullable()->change();

            $table->index(['tournament_id', 'team_category', 'team_bracket', 'round_no'], 'matches_tournament_category_bracket_round_idx');
            $table->index(['user_id', 'tournament_id'], 'matches_user_tournament_idx');
            $table->index(['user_id', 'team1_score', 'team2_score'], 'matches_user_scores_idx');

            $table->foreign('tournament_id', 'matches_tournament_fk')
                ->references('id')->on('tournaments')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('team1_id', 'matches_team1_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('team2_id', 'matches_team2_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('winner_id', 'matches_winner_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'matches_user_fk')
                ->references('id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->change();
            $table->unsignedBigInteger('team1_id')->nullable()->change();
            $table->unsignedBigInteger('team2_id')->nullable()->change();
            $table->unsignedBigInteger('winner_id')->nullable()->change();

            $table->index(['tournament_id', 'team_category', 'match_no'], 'playoff_tournament_category_match_idx');
            $table->index(['tournament_id', 'team_category', 'match_type'], 'playoff_tournament_category_type_idx');
            $table->index(['user_id', 'tournament_id'], 'playoff_user_tournament_idx');
            $table->index(['user_id', 'team2_id', 'team1_score', 'team2_score'], 'playoff_user_status_idx');

            $table->foreign('tournament_id', 'playoff_tournament_fk')
                ->references('id')->on('tournaments')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('team1_id', 'playoff_team1_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('team2_id', 'playoff_team2_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('winner_id', 'playoff_winner_fk')
                ->references('id')->on('teams')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'playoff_user_fk')
                ->references('id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id', 'sessions_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->foreign('email', 'password_reset_tokens_email_fk')
                ->references('email')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropForeign('password_reset_tokens_email_fk');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign('sessions_user_fk');
        });

        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->dropForeign('playoff_tournament_fk');
            $table->dropForeign('playoff_team1_fk');
            $table->dropForeign('playoff_team2_fk');
            $table->dropForeign('playoff_winner_fk');
            $table->dropForeign('playoff_user_fk');
            $table->dropIndex('playoff_tournament_category_match_idx');
            $table->dropIndex('playoff_tournament_category_type_idx');
            $table->dropIndex('playoff_user_tournament_idx');
            $table->dropIndex('playoff_user_status_idx');
            $table->bigInteger('tournament_id')->change();
            $table->bigInteger('team1_id')->nullable()->change();
            $table->bigInteger('team2_id')->nullable()->change();
            $table->bigInteger('winner_id')->nullable()->change();
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign('matches_tournament_fk');
            $table->dropForeign('matches_team1_fk');
            $table->dropForeign('matches_team2_fk');
            $table->dropForeign('matches_winner_fk');
            $table->dropForeign('matches_user_fk');
            $table->dropIndex('matches_tournament_category_bracket_round_idx');
            $table->dropIndex('matches_user_tournament_idx');
            $table->dropIndex('matches_user_scores_idx');
            $table->bigInteger('tournament_id')->change();
            $table->bigInteger('team1_id')->change();
            $table->bigInteger('team2_id')->change();
            $table->bigInteger('winner_id')->nullable()->change();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign('teams_tournament_fk');
            $table->dropForeign('teams_user_fk');
            $table->dropIndex('teams_tournament_category_bracket_idx');
            $table->dropIndex('teams_user_tournament_idx');
            $table->bigInteger('tournament_id')->change();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropForeign('tournaments_user_fk');
            $table->dropIndex('tournaments_user_date_idx');
            $table->dropIndex('tournaments_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_approval_name_idx');
        });
    }
};
