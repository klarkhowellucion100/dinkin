<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('matches', 'is_finalized')) {
            Schema::table('matches', function (Blueprint $table): void {
                $table->boolean('is_finalized')->default(false)->after('winner_id');
            });
        }

        DB::table('matches')
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereColumn('team1_score', '!=', 'team2_score')
            ->update(['is_finalized' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('matches', 'is_finalized')) {
            Schema::table('matches', function (Blueprint $table): void {
                $table->dropColumn('is_finalized');
            });
        }
    }
};
