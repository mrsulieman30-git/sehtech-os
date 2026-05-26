<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_access_grants', function (Blueprint $table) {
            $table->id();
            $table->uuid('entity_id');
            $table->string('entity_type'); // App\Models\Task or App\Models\ProjectNode
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['entity_id', 'entity_type', 'user_id'], 'entity_access_unique');
            $table->index(['entity_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_access_grants');
    }
};
