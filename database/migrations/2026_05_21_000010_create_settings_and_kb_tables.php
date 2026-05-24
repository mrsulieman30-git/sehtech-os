<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Knowledge Base Articles
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->longText('content')->nullable();
            $table->jsonb('tags')->nullable();
            $table->enum('access_level', ['public', 'internal', 'agent_only'])->default('internal');
            $table->boolean('is_published')->default(false);
            $table->uuid('author_id')->nullable();
            $table->integer('version')->default(1);
            $table->enum('embedding_status', ['pending', 'indexed', 'failed'])->default('pending');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
        });

        // Global System Settings (Key-Value JSONB store)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('value')->nullable();
            $table->timestamps();
        });

        // Seed default settings immediately
        DB::table('system_settings')->insert([
            ['key' => 'company_profile', 'value' => json_encode(['name' => 'SEHTECH', 'currency' => 'USD', 'timezone' => 'Africa/Mogadishu']), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'appearance', 'value' => json_encode(['theme' => 'light', 'dock_position' => 'bottom', 'compact_mode' => false]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'localization', 'value' => json_encode(['default_lang' => 'en', 'date_format' => 'YYYY-MM-DD']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('kb_articles');
    }
};
