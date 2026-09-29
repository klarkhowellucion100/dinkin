<?php

use App\Models\User;
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
        Schema::create('playoff_matches_controllers', function (Blueprint $table) {
          $table->id();

            $table->string('code')->unique();

            $table->bigInteger('tournament_id')
                ->index('playoff_tournament_index');

            $table->string('team_bracket')
                ->index('playoff_bracket_index');

            $table->string('team_category')
                ->index('playoff_category_index');

            /*
             * SF1  = Semifinal 1
             * SF2  = Semifinal 2
             * 3RD  = 3rd Place
             * FINAL = Championship Final
             */
            $table->string('match_type')
                ->index('playoff_match_type_index');

            $table->string('match_no')
                ->nullable()
                ->index('playoff_match_no_index');

            $table->bigInteger('team1_id')
                ->nullable()
                ->index('playoff_team1_index');

            $table->bigInteger('team2_id')
                ->nullable()
                ->index('playoff_team2_index');

            $table->bigInteger('team1_score')
                ->nullable();

            $table->bigInteger('team2_score')
                ->nullable();

            $table->bigInteger('winner_id')
                ->nullable()
                ->index('playoff_winner_index');

            $table->foreignIdFor(User::class)
                ->index('playoff_user_index');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playoff_matches_controllers');
    }
};
