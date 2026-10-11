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
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedInteger('schedule_no')->nullable()->after('match_no');
            $table->unsignedInteger('court_no')->nullable()->after('schedule_no');
            $table->index(['tournament_id', 'schedule_no', 'court_no'], 'matches_court_schedule_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_court_schedule_idx');
            $table->dropColumn(['schedule_no', 'court_no']);
        });
    }
};
