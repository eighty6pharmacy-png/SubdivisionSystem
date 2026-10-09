<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_lots', function (Blueprint $table) {
            $table->index('lot_id');
        });
        
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('lot_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('utility_bill_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('lot_id');
            $table->index('buyer_master_list_id');
        });

        Schema::table('downpayments', function (Blueprint $table) {
            $table->index('reservation_id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->index('host_id');
            $table->index('guard_id');
        });

        Schema::table('downpayment_histories', function (Blueprint $table) {
            $table->index('downpayment_id');
        });

        Schema::table('financing_records', function (Blueprint $table) {
            $table->index('buyer_master_list_id');
            $table->index('lot_id');
        });

        Schema::table('financing_payment_histories', function (Blueprint $table) {
            $table->index('financing_record_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('buyer_master_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_lots', function (Blueprint $table) {
            $table->dropIndex(['lot_id']);
        });

        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['lot_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['utility_bill_id']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['lot_id']);
            $table->dropIndex(['buyer_master_list_id']);
        });

        Schema::table('downpayments', function (Blueprint $table) {
            $table->dropIndex(['reservation_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['host_id']);
            $table->dropIndex(['guard_id']);
        });

        Schema::table('downpayment_histories', function (Blueprint $table) {
            $table->dropIndex(['downpayment_id']);
        });

        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropIndex(['buyer_master_list_id']);
            $table->dropIndex(['lot_id']);
        });

        Schema::table('financing_payment_histories', function (Blueprint $table) {
            $table->dropIndex(['financing_record_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['buyer_master_list_id']);
        });
    }
};
