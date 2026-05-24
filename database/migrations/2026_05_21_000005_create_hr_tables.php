<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extended Employee Profiles (Links to core Users table)
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->uuid('manager_id')->nullable();
            
            $table->string('job_title');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->date('hire_date');
            $table->decimal('salary', 15, 2)->nullable();
            
            $table->string('national_id')->nullable();
            $table->string('tax_id')->nullable();
            $table->jsonb('bank_details')->nullable();
            $table->jsonb('emergency_contact')->nullable();
            
            $table->integer('annual_leave_balance')->default(21);
            $table->integer('sick_leave_balance')->default(14);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });

        // Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            
            $table->enum('type', ['annual', 'sick', 'maternity', 'paternity', 'unpaid', 'bereavement']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            $table->uuid('reviewed_by')->nullable();
            $table->text('reviewer_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_profiles');
    }
};
