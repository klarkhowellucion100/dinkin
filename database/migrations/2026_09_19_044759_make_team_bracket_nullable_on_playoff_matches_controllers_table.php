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
        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->string('team_bracket')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playoff_matches_controllers', function (Blueprint $table) {
            $table->string('team_bracket')
                ->nullable(false)
                ->change();
        });
    }
};
