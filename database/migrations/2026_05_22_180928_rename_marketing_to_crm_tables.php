<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create new CRM tables
        Schema::create('crm_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('crm_account_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('crm_account_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('value', 10, 2)->nullable();
            $table->string('stage')->default('lead'); 
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_close_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); 
            $table->text('description')->nullable();
            $table->uuid('activatable_id');
            $table->string('activatable_type');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type'); 
            $table->string('status')->default('draft');
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Migrate legacy Clients to CrmAccounts and Deals
        $clients = DB::table('clients')->get();
        foreach ($clients as $client) {
            $accountId = \Illuminate\Support\Str::uuid();
            $orgName = $client->organization ?: ($client->first_name . ' ' . $client->last_name . ' (Org)');
            
            DB::table('crm_accounts')->insert([
                'id' => $accountId,
                'name' => $orgName,
                'city' => $client->city,
                'country' => $client->country,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at
            ]);

            DB::table('crm_contacts')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'crm_account_id' => $accountId,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at
            ]);

            DB::table('crm_deals')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'crm_account_id' => $accountId,
                'title' => 'Opportunity: ' . $orgName,
                'stage' => $client->status, // Map legacy client status to deal stage
                'assigned_to' => $client->assigned_to,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at
            ]);
        }

        // 3. Drop empty marketing tables
        Schema::dropIfExists('marketing_channels');
        Schema::dropIfExists('marketing_contents');
        Schema::dropIfExists('marketing_activities');
        Schema::dropIfExists('marketing_deals');
        Schema::dropIfExists('marketing_contacts');
        Schema::dropIfExists('marketing_accounts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_channels');
        Schema::dropIfExists('crm_contents');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_accounts');
    }
};
