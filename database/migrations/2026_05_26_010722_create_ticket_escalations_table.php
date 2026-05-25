<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->index();
            $table->uuid('escalated_by')->nullable(); // user id
            $table->string('escalated_to_team'); // devops, product, etc.
            $table->uuid('escalated_to_user')->nullable();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, rejected, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_escalations');
    }
};
