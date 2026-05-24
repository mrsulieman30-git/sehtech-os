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
        Schema::table('research_ideas', function (Blueprint $table) {
            $table->string('priority')->default('P2'); // P0, P1, P2
            $table->json('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_ideas', function (Blueprint $table) {
            $table->dropColumn(['priority', 'tags']);
        });
    }
};
