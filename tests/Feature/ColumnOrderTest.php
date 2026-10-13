<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ColumnOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_tables_list_keys_before_details_and_timestamps_last(): void
    {
        $prefixes = [
            'users' => ['id', 'last_name', 'first_name', 'middle_name'],
            'admins' => ['user_id'],
            'finance_officers' => ['user_id'],
            'guards' => ['user_id'],
            'residents' => ['user_id', 'payment_behavior'],
            'buyers' => ['id', 'user_id', 'lot_id'],
            'lots' => ['id', 'block', 'lot_number'],
            'resident_lots' => ['resident_id', 'lot_id'],
            'reservations' => ['id', 'user_id', 'lot_id', 'buyer_id', 'last_name', 'first_name'],
            'downpayments' => ['id', 'reservation_id'],
            'downpayment_histories' => ['id', 'downpayment_id'],
            'financing_records' => ['id', 'buyer_id', 'lot_id'],
            'financing_payment_histories' => ['id', 'financing_record_id'],
            'utility_bills' => ['id', 'resident_id', 'lot_id', 'billed_to_name'],
            'payments' => ['id', 'utility_bill_id'],
            'incidents' => ['id', 'resident_id', 'reporter_name'],
            'visitors' => ['id', 'resident_id', 'guard_id', 'host_name'],
            'announcements' => ['id', 'admin_id'],
            'announcement_recipients' => ['announcement_id', 'resident_id'],
            'appointments' => ['id', 'last_name', 'first_name', 'middle_name'],
            'settings' => ['key', 'value'],
        ];

        foreach ($prefixes as $table => $prefix) {
            $columns = Schema::getColumnListing($table);
            $this->assertSame($prefix, array_slice($columns, 0, count($prefix)), $table);

            $timestamps = array_values(array_filter(
                ['created_at', 'updated_at', 'deleted_at'],
                fn (string $column) => in_array($column, $columns, true)
            ));
            $this->assertSame($timestamps, array_slice($columns, -count($timestamps)), $table);
        }
    }
}
