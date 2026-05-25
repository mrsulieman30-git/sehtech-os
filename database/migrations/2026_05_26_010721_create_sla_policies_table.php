<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('tier'); // enterprise, standard, basic
            $table->string('priority'); // P1, P2, P3, P4
            $table->integer('first_response_mins');
            $table->integer('resolution_mins');
            $table->json('business_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
