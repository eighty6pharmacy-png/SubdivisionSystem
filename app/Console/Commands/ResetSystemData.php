<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetSystemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-data {--force : Force execution without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe transactional data while preserving system accounts and re-seeding default roles/users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️ WARNING: This will permanently delete all transactional records (bills, payments, buyers, reservations, visitors, announcements, incidents, appointments). Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting system data reset...');

        // Disable foreign key checks for PostgreSQL / MySQL compatibility
        Schema::disableForeignKeyConstraints();

        try {
            // List of operational/transactional tables to clean out
            $tablesToTruncate = [
                'payments',
                'utility_bills',
                'downpayment_histories',
                'downpayments',
                'reservations',
                'financing_payment_histories',
                'financing_records',
                'buyer_master_lists',
                'user_lots',
                'visitors',
                'incidents',
                'announcements',
                'appointments',
                'notifications',
            ];

            foreach ($tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line("  ✓ Cleared table: {$table}");
                }
            }

            // Re-enable foreign key constraints
            Schema::enableForeignKeyConstraints();

            $this->info('Re-running DatabaseSeeder to ensure core system accounts exist...');
            
            // Run seeder to ensure default Admin, Security Guard, and Finance Officer accounts exist
            $this->call('db:seed', ['--force' => true]);

            $this->info('🚀 System data has been successfully reset! Core accounts are ready to use.');
            return 0;

        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            $this->error('Failed to reset system data: ' . $e->getMessage());
            return 1;
        }
    }
}
