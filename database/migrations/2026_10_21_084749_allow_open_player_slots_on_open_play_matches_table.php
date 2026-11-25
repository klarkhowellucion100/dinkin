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
            $table->foreignId('team1_player1_id')->nullable()->change();
            $table->foreignId('team2_player1_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_matches', function (Blueprint $table): void {
            $table->foreignId('team1_player1_id')->nullable(false)->change();
            $table->foreignId('team2_player1_id')->nullable(false)->change();
        });
    }
};
