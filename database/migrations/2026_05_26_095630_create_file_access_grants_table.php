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
        Schema::create('file_access_grants', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('path'); // The physical path in the central storage
            $table->string('access_level')->default('read'); // read, write
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // A user can only have one grant per specific path
            $table->unique(['user_id', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_access_grants');
    }
};
