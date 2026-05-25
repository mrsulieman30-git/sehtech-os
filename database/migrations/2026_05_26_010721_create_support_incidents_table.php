<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('incident_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('type'); // system_outage, database_failure, etc.
            $table->string('severity'); // P1, P2, P3, P4
            $table->string('status'); // investigating, identified, monitoring, resolved, closed
            $table->uuid('lead_engineer_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->json('affected_services')->nullable();
            $table->json('mitigation_steps')->nullable();
            $table->text('postmortem')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_incidents');
    }
};
