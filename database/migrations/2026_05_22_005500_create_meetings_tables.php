<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->enum('type', ['internal', 'external', 'video', 'audio', 'in_person'])->default('video');
            
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_minutes')->nullable();
            
            $table->uuid('project_id')->nullable();
            $table->uuid('client_id')->nullable();
            
            $table->string('video_room_id')->nullable(); // For LiveKit integration
            $table->string('join_url')->nullable();
            $table->longText('agenda')->nullable();
            $table->longText('notes')->nullable();
            
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->uuid('meeting_id');
            $table->uuid('user_id')->nullable(); // Internal users
            $table->string('external_email')->nullable(); // External clients
            $table->enum('status', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('meetings');
    }
};
