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
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->time('schedule_start_time')->nullable()->after('schedule_mode');
            $table->unsignedSmallInteger('match_duration_minutes')->nullable()->after('schedule_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn(['schedule_start_time', 'match_duration_minutes']);
        });
    }
};
