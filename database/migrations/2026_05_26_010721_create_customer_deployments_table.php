<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->index();
            $table->string('name');
            $table->string('deployment_type'); // cloud, on_premise, hybrid
            $table->string('status'); // active, maintenance, deprecated
            $table->string('software_version');
            $table->json('installed_modules')->nullable();
            $table->json('integrations')->nullable(); // HL7, FHIR, etc.
            $table->json('server_details')->nullable();
            $table->string('sla_tier')->nullable();
            $table->timestamp('contract_expires_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_deployments');
    }
};
