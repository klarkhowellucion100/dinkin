<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->boolean('is_finalized')->default(false)->after('winner_id');
        });

        DB::table('playoff_matches_controllers')
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereColumn('team1_score', '!=', 'team2_score')
            ->update(['is_finalized' => true]);

        DB::table('playoff_matches_controllers')
            ->whereNull('winner_id')
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereColumn('team1_score', '>', 'team2_score')
            ->update(['winner_id' => DB::raw('team1_id')]);

        DB::table('playoff_matches_controllers')
            ->whereNull('winner_id')
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereColumn('team2_score', '>', 'team1_score')
            ->update(['winner_id' => DB::raw('team2_id')]);
    }

    public function down(): void
    {
        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->dropColumn('is_finalized');
        });
    }
};
