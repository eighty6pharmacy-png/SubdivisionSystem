<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_master_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Personal Info
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('civil_status')->nullable();
            $table->string('spouse_name')->nullable();
            $table->text('present_address')->nullable();
            $table->text('postal_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('proof_of_id')->nullable();
            $table->string('pagibig_number')->nullable();
            
            // Property Info
            $table->string('block_no')->nullable();
            $table->string('lot_no')->nullable();
            $table->string('tct_no')->nullable();
            $table->string('pid')->nullable();
            $table->string('tax_dec_no')->nullable();
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->text('description')->nullable();
            
            // Financial Info
            $table->decimal('contract_amount', 12, 2)->nullable();
            $table->decimal('improvement_amount', 12, 2)->nullable();
            $table->decimal('equity', 12, 2)->nullable();
            $table->decimal('loan_base', 12, 2)->nullable();
            $table->integer('loan_term')->nullable();
            $table->decimal('monthly_amortization', 10, 2)->nullable();
            $table->decimal('mri_sri', 10, 2)->nullable();
            $table->decimal('insurance', 10, 2)->nullable();
            $table->decimal('mri_ds_1time', 10, 2)->nullable();
            $table->decimal('nonlife_1time', 10, 2)->nullable();
            $table->decimal('retention', 10, 2)->nullable();
            $table->decimal('total_deductions', 12, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_master_lists');
    }
};
