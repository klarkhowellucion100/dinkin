<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Nullable;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->bigInteger('tournament_id')->index('matches_tournament_index');
            $table->string('team_bracket')->index('matches_bracket_index');
            $table->string('team_category')->index('matches_category_index');
            $table->string('round_no')->nullable()->index('matches_round_index');
            $table->string('match_no')->nullable()->index('matches_match_index');
            $table->bigInteger('team1_id')->index('matches_team1_index');
            $table->bigInteger('team2_id')->index('matches_team2_index');
            $table->bigInteger('team1_score')->nullable();
            $table->bigInteger('team2_score')->nullable();
            $table->bigInteger('winner_id')->nullable()->index('matches_winner_index');
            $table->foreignIdFor(User::class)->index('matches_user_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
