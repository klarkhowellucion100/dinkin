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
        Schema::create('randomizer_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('randomizer_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('bracket_number');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['randomizer_session_id', 'bracket_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('randomizer_entries');
    }
};
