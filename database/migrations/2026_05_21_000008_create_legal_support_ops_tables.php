<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Legal Contracts Table
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('file_id')->nullable(); // Link to CFS
            $table->string('title');
            $table->enum('status', ['draft', 'sent', 'signed', 'active', 'expired', 'terminated'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Support Tickets Table
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'escalated', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['p0', 'p1', 'p2', 'p3'])->default('p2');
            $table->string('category')->default('general');
            $table->string('product')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('sla_due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Support Ticket Replies
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable(); // Employee or Client
            $table->text('body');
            $table->boolean('is_internal')->default(false); // True for internal support notes
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 4. Operations Infrastructure Assets
        Schema::create('infrastructure_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['domain', 'server', 'ssl', 'license', 'saas']);
            $table->string('provider');
            $table->date('expiry_date');
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_assets');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('contracts');
    }
};
