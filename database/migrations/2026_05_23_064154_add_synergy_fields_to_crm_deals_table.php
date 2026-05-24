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
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->text('requirements')->nullable();
            $table->string('payment_type')->default('one_time'); // one_time, recurring
            $table->string('recurring_frequency')->nullable(); // monthly, yearly, etc.
            $table->decimal('recurring_amount', 12, 2)->nullable();
            $table->date('collection_date')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->uuid('legal_contract_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->dropColumn([
                'requirements',
                'payment_type',
                'recurring_frequency',
                'recurring_amount',
                'collection_date',
                'contract_file_path',
                'legal_contract_id'
            ]);
        });
    }
};
