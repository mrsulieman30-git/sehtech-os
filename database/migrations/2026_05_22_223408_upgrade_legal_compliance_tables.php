<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Convert contracts.client_id to crm_account_id
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->uuid('crm_account_id')->nullable()->after('id');
            $table->foreign('crm_account_id')->references('id')->on('crm_accounts')->onDelete('cascade');
        });

        // 2. Contract Templates
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // NDA, MSA, DPA, etc.
            $table->text('ai_prompt')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
        });

        // 3. Compliance Frameworks
        Schema::create('compliance_frameworks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Compliance Controls
        Schema::create('compliance_controls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compliance_framework_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['compliant', 'non_compliant', 'gap', 'not_applicable'])->default('gap');
            $table->text('evidence')->nullable();
            $table->timestamps();
            
            $table->foreign('compliance_framework_id')->references('id')->on('compliance_frameworks')->onDelete('cascade');
        });

        // 5. Risk Register
        Schema::create('risk_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('likelihood', ['low', 'medium', 'high'])->default('medium');
            $table->enum('impact', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'mitigated', 'accepted', 'closed'])->default('open');
            $table->text('mitigation_plan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_registers');
        Schema::dropIfExists('compliance_controls');
        Schema::dropIfExists('compliance_frameworks');
        Schema::dropIfExists('contract_templates');
        
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['crm_account_id']);
            $table->dropColumn('crm_account_id');
            $table->uuid('client_id')->nullable();
            // Ignoring re-adding foreign key to old clients table to simplify rollback
        });
    }
};
