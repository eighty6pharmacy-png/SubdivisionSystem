<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend payments table to support utility bills, downpayments, and financing records.
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('downpayment_id')->nullable()->after('utility_bill_id');
            $table->unsignedBigInteger('financing_record_id')->nullable()->after('downpayment_id');
            $table->string('status')->default('Paid')->after('method');

            $table->index('downpayment_id');
            $table->index('financing_record_id');
            $table->foreign('downpayment_id')->references('id')->on('downpayments')->restrictOnDelete();
            $table->foreign('financing_record_id')->references('id')->on('financing_records')->restrictOnDelete();
        });

        // Make utility_bill_id nullable and enforce valid status.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN utility_bill_id DROP NOT NULL');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK ((status)::text = 'Paid'::text)");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('utility_bill_id')->nullable()->change();
            });
        }

        // 2. Copy existing downpayment_histories rows into payments.
        if (Schema::hasTable('downpayment_histories')) {
            DB::statement("
                INSERT INTO payments (
                    downpayment_id,
                    amount_paid,
                    method,
                    status,
                    trn,
                    paymongo_payment_id,
                    payment_date,
                    created_at,
                    updated_at
                )
                SELECT
                    downpayment_id,
                    amount,
                    payment_method,
                    status,
                    trn,
                    paymongo_payment_id,
                    payment_date,
                    created_at,
                    updated_at
                FROM downpayment_histories
            ");
        }

        // 3. Copy existing financing_payment_histories rows into payments.
        if (Schema::hasTable('financing_payment_histories')) {
            DB::statement("
                INSERT INTO payments (
                    financing_record_id,
                    amount_paid,
                    method,
                    status,
                    trn,
                    paymongo_payment_id,
                    payment_date,
                    created_at,
                    updated_at
                )
                SELECT
                    financing_record_id,
                    amount_paid,
                    COALESCE(payment_method, 'Office Payment'),
                    status,
                    COALESCE(trn, 'FIN-' || id),
                    paymongo_payment_id,
                    payment_date,
                    created_at,
                    updated_at
                FROM financing_payment_histories
            ");
        }

        // 4. Enforce that every payment belongs to exactly one parent obligation.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE payments
                ADD CONSTRAINT payments_single_parent_check
                CHECK (((utility_bill_id IS NOT NULL)::int + (downpayment_id IS NOT NULL)::int + (financing_record_id IS NOT NULL)::int) = 1)
            ');
        }

        // 5. Drop the redundant payment history tables.
        Schema::dropIfExists('downpayment_histories');
        Schema::dropIfExists('financing_payment_histories');
    }

    public function down(): void
    {
        Schema::create('downpayment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('downpayment_id');
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date');
            $table->string('trn')->unique();
            $table->string('payment_method')->default('Office Payment');
            $table->string('paymongo_payment_id')->nullable();
            $table->string('status')->default('Paid');
            $table->timestamps();

            $table->index('downpayment_id');
            $table->foreign('downpayment_id')->references('id')->on('downpayments')->restrictOnDelete();
        });

        Schema::create('financing_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financing_record_id');
            $table->decimal('amount_paid', 15, 2);
            $table->timestamp('payment_date');
            $table->string('trn')->nullable();
            $table->string('payment_method')->nullable()->default('Office Payment');
            $table->string('paymongo_payment_id')->nullable();
            $table->string('status')->default('Paid');
            $table->timestamps();

            $table->index('financing_record_id');
            $table->foreign('financing_record_id')->references('id')->on('financing_records')->restrictOnDelete();
        });

        DB::statement("
            INSERT INTO downpayment_histories (downpayment_id, amount, payment_date, trn, payment_method, paymongo_payment_id, status, created_at, updated_at)
            SELECT downpayment_id, amount_paid, payment_date, trn, COALESCE(method, 'Office Payment'), paymongo_payment_id, status, created_at, updated_at
            FROM payments
            WHERE downpayment_id IS NOT NULL
        ");

        DB::statement("
            INSERT INTO financing_payment_histories (financing_record_id, amount_paid, payment_date, trn, payment_method, paymongo_payment_id, status, created_at, updated_at)
            SELECT financing_record_id, amount_paid, payment_date, trn, method, paymongo_payment_id, status, created_at, updated_at
            FROM payments
            WHERE financing_record_id IS NOT NULL
        ");

        DB::table('payments')->whereNull('utility_bill_id')->delete();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_single_parent_check');
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
            DB::statement('ALTER TABLE payments ALTER COLUMN utility_bill_id SET NOT NULL');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['downpayment_id']);
            $table->dropForeign(['financing_record_id']);
            $table->dropIndex(['downpayment_id']);
            $table->dropIndex(['financing_record_id']);
            $table->dropColumn(['downpayment_id', 'financing_record_id', 'status']);
        });
    }
};
