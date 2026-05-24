<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('source')->nullable();
            
            // Pipeline Statuses
            $table->enum('status', [
                'lead', 'contacted', 'demo_scheduled', 'proposal_sent', 
                'negotiation', 'closed_won', 'closed_lost'
            ])->default('lead');
            
            // Post-conversion Client Portal Status
            $table->enum('account_status', ['pending', 'active', 'suspended'])->nullable();
            
            $table->jsonb('product_interest')->nullable();
            $table->string('budget_range')->nullable();
            $table->enum('priority', ['hot', 'warm', 'cold'])->default('warm');
            
            $table->uuid('portal_user_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('meta')->nullable();
            $table->uuid('created_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('portal_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Activity Log for CRM
        Schema::create('client_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('user_id')->nullable();
            $table->string('type'); // call, email, meeting, note, status_change
            $table->text('description');
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_activities');
        Schema::dropIfExists('clients');
    }
};
