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
        Schema::table('financing_records', function (Blueprint $table) {
            $table->renameColumn('pag_ibig_sri_mri', 'sri_mri');
            $table->renameColumn('pag_ibig_non_life_ins', 'non_life_insurance');
            $table->renameColumn('pag_ibig_inspection_fee', 'inspection_fee');
            $table->renameColumn('pag_ibig_retention', 'retention_fee');
            $table->renameColumn('pag_ibig_loan_release', 'loan_release');
            $table->renameColumn('pag_ibig_loan_net_proceeds', 'net_loan_proceeds');
            
            $table->dropColumn('pag_ibig_interim_mri');
            $table->decimal('improvements_fee', 15, 2)->default(0)->after('other_exp_annotation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            $table->renameColumn('sri_mri', 'pag_ibig_sri_mri');
            $table->renameColumn('non_life_insurance', 'pag_ibig_non_life_ins');
            $table->renameColumn('inspection_fee', 'pag_ibig_inspection_fee');
            $table->renameColumn('retention_fee', 'pag_ibig_retention');
            $table->renameColumn('loan_release', 'pag_ibig_loan_release');
            $table->renameColumn('net_loan_proceeds', 'pag_ibig_loan_net_proceeds');
            
            $table->decimal('pag_ibig_interim_mri', 15, 2)->default(0);
            $table->dropColumn('improvements_fee');
        });
    }
};
