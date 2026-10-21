<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Partial unique indexes use syntax supported by both PostgreSQL and SQLite.
     *
     * @var array<string, array{0: string, 1: string}> index name => [table, definition]
     */
    private array $uniqueIndexes = [
        'users_email_active_unique' => ['users', '(email) WHERE deleted_at IS NULL'],
        'users_buyer_master_list_id_unique' => ['users', '(buyer_master_list_id) WHERE buyer_master_list_id IS NOT NULL AND deleted_at IS NULL'],

        'reservations_active_lot_unique' => ['reservations', "(lot_id) WHERE status IN ('Pending', 'Reserved', 'Converted') AND deleted_at IS NULL"],
        'downpayments_reservation_id_unique' => ['downpayments', '(reservation_id) WHERE deleted_at IS NULL'],
        'financing_records_buyer_master_list_id_unique' => ['financing_records', '(buyer_master_list_id)'],
        'utility_bills_lot_type_period_unique' => ['utility_bills', '(lot_id, type, billing_period) WHERE deleted_at IS NULL'],

        'utility_bills_paymongo_checkout_id_unique' => ['utility_bills', '(paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL'],
        'downpayments_paymongo_checkout_id_unique' => ['downpayments', '(paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL'],
        'financing_records_paymongo_checkout_id_unique' => ['financing_records', '(paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL'],

        'payments_paymongo_payment_id_unique' => ['payments', '(paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL'],
        'downpayment_histories_paymongo_payment_id_unique' => ['downpayment_histories', '(paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL'],
        'financing_payment_histories_paymongo_payment_id_unique' => ['financing_payment_histories', '(paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL'],

        'downpayment_histories_trn_unique' => ['downpayment_histories', '(trn)'],
        'financing_payment_histories_trn_unique' => ['financing_payment_histories', '(trn) WHERE trn IS NOT NULL'],
    ];

    /**
     * @var array<string, list<string>> table => money columns that must be >= 0
     */
    private array $nonNegative = [
        'utility_bills' => ['amount'],
        'payments' => ['amount_paid'],
        'reservations' => ['amount'],
        'downpayments' => ['amount', 'balance'],
        'downpayment_histories' => ['amount'],
        'financing_records' => ['receivables', 'amount_paid', 'balance'],
        'financing_payment_histories' => ['amount_paid'],
    ];

    /**
     * Every value currently written by the app or offered by a view, with existing casing.
     *
     * @var array<string, list<string>>
     */
    private array $statuses = [
        'users' => ['Active', 'Archived'],
        'lots' => ['Available', 'Reserved', 'Occupied', 'Vacant House', 'Vacant Lot', 'Under Construction', 'Unassigned'],
        'reservations' => ['Pending', 'Reserved', 'Converted', 'Cancelled'],
        'downpayments' => ['Pending', 'Good Standing', 'Fully Paid', 'Delinquent', 'Paid'],
        'downpayment_histories' => ['Paid'],
        'financing_records' => ['Pending', 'Processing', 'Released', 'Cleared'],
        'financing_payment_histories' => ['Paid'],
        'utility_bills' => ['unpaid', 'partially_paid', 'paid', 'pending', 'overdue'],
        'incidents' => ['pending', 'progress', 'resolved', 'Open / Pending', 'In Progress', 'Resolved'],
        'visitors' => ['Pending', 'Approved', 'Entered', 'Rejected', 'Awaiting Admin Approval'],
        'announcements' => ['Published', 'published', 'Archived', 'archived', 'Scheduled', 'scheduled', 'Draft', 'draft'],
        'appointments' => ['Pending', 'Scheduled', 'Completed', 'Cancelled'],
    ];

    public function up(): void
    {
        foreach ($this->uniqueIndexes as $name => [$table, $definition]) {
            DB::statement("CREATE UNIQUE INDEX {$name} ON {$table} {$definition}");
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->nonNegative as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$column}_non_negative CHECK ({$column} >= 0)");
            }
        }

        foreach ($this->statuses as $table => $values) {
            $list = implode(', ', array_map(fn (string $v) => DB::getPdo()->quote($v), $values));
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_status_check CHECK (status IN ({$list}))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (array_keys($this->statuses) as $table) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_status_check");
            }

            foreach ($this->nonNegative as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_{$column}_non_negative");
                }
            }
        }

        foreach (array_keys($this->uniqueIndexes) as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }
};
