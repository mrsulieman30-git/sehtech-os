<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projects Table
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['hms', 'clinic', 'pharmacy', 'dental', 'inventory', 'video', 'internal'])->default('internal');
            // client_id will be linked to CRM later, using nullable string/uuid for now
            $table->uuid('client_id')->nullable(); 
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->uuid('manager_id')->nullable();
            $table->jsonb('tech_stack')->nullable();
            $table->jsonb('meta')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Tasks Table
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('sprint_id')->nullable(); // For future sprint table integration
            $table->uuid('milestone_id')->nullable(); // For future milestone table integration
            $table->uuid('parent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['backlog', 'todo', 'in_progress', 'review', 'qa', 'done', 'deployed'])->default('backlog');
            $table->enum('priority', ['p0', 'p1', 'p2', 'p3'])->default('p2');
            $table->integer('story_points')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->uuid('reporter_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->jsonb('labels')->nullable();
            $table->integer('attachments_count')->default(0);
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add self-referencing FK separately to avoid Postgres constraint error
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('tasks')->onDelete('cascade');
        });

        // Task Assignees Pivot
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
    }
};
