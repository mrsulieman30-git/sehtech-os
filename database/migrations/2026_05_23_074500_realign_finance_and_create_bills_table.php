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
        // 1. Drop old foreign keys that point to deprecated clients table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        // 2. Add new foreign key constraints pointing to crm_accounts
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('crm_accounts')->onDelete('restrict');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('crm_accounts')->onDelete('restrict');
        });

        // 3. Create bills/expenses table for Accounts Payable
        Schema::create('crm_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bill_number')->unique();
            $table->string('vendor_name');
            $table->string('category'); // e.g. software, marketing, office, travel, payroll, other
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_bills');
    }
};
