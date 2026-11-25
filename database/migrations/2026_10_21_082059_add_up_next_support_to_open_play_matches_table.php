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
        Schema::table('open_play_matches', function (Blueprint $table): void {
            $table->index(['open_play_session_id', 'status', 'court_no'], 'open_play_matches_dispatch_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_matches', function (Blueprint $table): void {
            $table->dropIndex('open_play_matches_dispatch_idx');
        });
    }
};
