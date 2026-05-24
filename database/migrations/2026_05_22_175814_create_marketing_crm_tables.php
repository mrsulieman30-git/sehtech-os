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
        Schema::create('marketing_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('marketing_account_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('marketing_account_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('value', 10, 2)->nullable();
            $table->string('stage')->default('lead'); // lead, qualified, proposal, negotiation, won, lost
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_close_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('marketing_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // call, email, whatsapp, visit, demo
            $table->text('description')->nullable();
            $table->uuid('activatable_id');
            $table->string('activatable_type'); // Morph to Account, Contact, Deal
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type'); // post, article, video, case_study
            $table->string('status')->default('draft'); // draft, published
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // LinkedIn, Twitter, Blog, etc.
            $table->string('url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_channels');
        Schema::dropIfExists('marketing_contents');
        Schema::dropIfExists('marketing_activities');
        Schema::dropIfExists('marketing_deals');
        Schema::dropIfExists('marketing_contacts');
        Schema::dropIfExists('marketing_accounts');
    }
};
