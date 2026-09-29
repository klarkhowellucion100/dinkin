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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->bigInteger('tournament_id')->index('teams_tournament_index');
            $table->string('team_no');
            $table->string('team_name');
            $table->string('team_bracket')->index('teams_bracket_index');
            $table->string('team_category')->index('teams_category_index');
            $table->string('team_status')->index('teams_status_index')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
