<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('summary');
            $table->longText('content')->nullable(); // Rich text content
            
            $table->enum('category', ['hms', 'clinic', 'pharmacy', 'dental', 'inventory', 'video', 'internal', 'other'])->default('other');
            $table->enum('status', ['draft', 'under_review', 'approved', 'rejected', 'in_development'])->default('draft');
            
            $table->integer('vote_count')->default(0);
            
            $table->uuid('author_id');
            $table->uuid('converted_project_id')->nullable(); // Links to Development Office if approved
            
            $table->jsonb('meta')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            // 'projects' table was created in Phase 4
            $table->foreign('converted_project_id')->references('id')->on('projects')->onDelete('set null'); 
        });

        Schema::create('research_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('idea_id');
            $table->uuid('user_id');
            $table->text('body');
            $table->timestamps();

            $table->foreign('idea_id')->references('id')->on('research_ideas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_comments');
        Schema::dropIfExists('research_ideas');
    }
};
