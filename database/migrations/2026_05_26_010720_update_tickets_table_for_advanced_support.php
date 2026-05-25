<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('module')->nullable()->after('product');
            $table->string('submodule')->nullable()->after('module');
            $table->string('environment')->nullable()->after('submodule');
            $table->string('software_version')->nullable()->after('environment');
            $table->string('severity')->nullable()->after('priority');
            $table->string('impact_level')->nullable()->after('severity');
            $table->uuid('incident_id')->nullable()->after('assigned_to');
            $table->uuid('parent_ticket_id')->nullable()->after('incident_id');
            $table->boolean('is_escalated')->default(false)->after('status');
            $table->string('sla_category')->nullable()->after('is_escalated');
            $table->string('source_channel')->default('portal')->after('sla_category');
            $table->timestamp('first_response_at')->nullable()->after('sla_due_date');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->json('tags')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'module', 'submodule', 'environment', 'software_version',
                'severity', 'impact_level', 'incident_id', 'parent_ticket_id',
                'is_escalated', 'sla_category', 'source_channel',
                'first_response_at', 'resolved_at', 'tags'
            ]);
        });
    }
};
