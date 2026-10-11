<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('buyer_master_list_id');
            $table->uuid('lot_id');

            // 1. Base Financials
            $table->decimal('contract_price', 15, 2)->default(0);
            $table->decimal('mbrdc_add_fees', 15, 2)->default(0);
            $table->decimal('other_exp_annotation', 15, 2)->default(0); // DA/SPA
            $table->decimal('improvements_fee', 15, 2)->default(0);

            // 2. Pag-IBIG / Bank Deductions
            $table->decimal('inspection_fee', 15, 2)->default(0);
            $table->decimal('retention_fee', 15, 2)->default(0);

            // 3. Totals & Equity
            $table->decimal('total_amt_due', 15, 2)->default(0);
            $table->decimal('mbrdc_turn_over_fee', 15, 2)->default(0);
            $table->decimal('total_consideration', 15, 2)->default(0);
            $table->decimal('paid_by_vendee_equity', 15, 2)->default(0); // Synced from Downpayments
            $table->decimal('mbrdc_amt_due_for_financing', 15, 2)->default(0);
            $table->decimal('additional_bill_of_materials', 15, 2)->default(0);

            // 4. Loan Release & Net Proceeds
            $table->decimal('loan_release', 15, 2)->default(0); // Gross Approved
            $table->decimal('net_loan_proceeds', 15, 2)->default(0); // Actual Received

            // 5. Developer Receivables & Balances
            $table->decimal('receivables', 15, 2)->default(0); // Shortfall owed to developer
            $table->decimal('amount_paid', 15, 2)->default(0); // Paid against receivables
            $table->decimal('balance', 15, 2)->default(0);

            // 6. Accounting Classification
            $table->decimal('rbgi_ap', 15, 2)->default(0);
            $table->decimal('mbrdc_ap_in_house', 15, 2)->default(0);
            $table->decimal('account_receivables', 15, 2)->default(0);
            $table->string('remarks')->nullable();

            $table->string('status')->default('Pending'); // Pending, Processing, Released, Cleared
            $table->date('loan_release_date')->nullable();

            // 7. Insurance
            $table->decimal('sri_mri', 15, 2)->default(0);
            $table->decimal('pag_ibig_non_life', 15, 2)->default(0);
            $table->decimal('interim_mri', 15, 2)->default(0);

            // true = Retention/Conversion is still held by Pag-IBIG/bank (deducted from net proceeds)
            $table->boolean('retention_on_hold')->default(true);
            $table->string('paymongo_checkout_id')->nullable();

            $table->timestamps();

            $table->index('buyer_master_list_id');
            $table->index('lot_id');

            $table->foreign('buyer_master_list_id')->references('id')->on('buyer_master_lists')->restrictOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_records');
    }
};
