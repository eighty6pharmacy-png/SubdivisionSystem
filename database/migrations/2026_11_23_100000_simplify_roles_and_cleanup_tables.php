<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace Spatie permission tables and empty role-split tables
     * with direct `role` and `payment_behavior` columns on `users`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('Resident')->after('status');
            }
            if (! Schema::hasColumn('users', 'payment_behavior')) {
                $table->string('payment_behavior', 100)->default('Not available yet')->after('role');
            }
        });

        // Backfill users.role from Spatie's model_has_roles + roles
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $assignments = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->select('model_has_roles.model_id', 'roles.name')
                ->get();

            foreach ($assignments as $row) {
                DB::table('users')
                    ->where('id', $row->model_id)
                    ->update(['role' => $row->name]);
            }
        }

        // Backfill users.payment_behavior from residents
        if (Schema::hasTable('residents')) {
            $residents = DB::table('residents')->select('user_id', 'payment_behavior')->get();
            foreach ($residents as $resident) {
                if ($resident->payment_behavior) {
                    DB::table('users')
                        ->where('id', $resident->user_id)
                        ->update(['payment_behavior' => $resident->payment_behavior]);
                }
            }
        }

        // Retarget foreign keys from admins/residents onto users(id) before dropping those tables
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'admin_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('resident_lots') && Schema::hasColumn('resident_lots', 'resident_id')) {
            Schema::table('resident_lots', function (Blueprint $table) {
                $table->dropForeign(['resident_id']);
                $table->foreign('resident_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('announcement_recipients') && Schema::hasColumn('announcement_recipients', 'resident_id')) {
            Schema::table('announcement_recipients', function (Blueprint $table) {
                $table->dropForeign(['resident_id']);
                $table->foreign('resident_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        foreach (['utility_bills', 'incidents', 'visitors'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'resident_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['resident_id']);
                    $table->foreign('resident_id')->references('id')->on('users')->nullOnDelete();
                });
            }
        }

        // Drop Spatie permission tables
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');

        // Drop redundant role-split tables
        Schema::dropIfExists('admins');
        Schema::dropIfExists('finance_officers');
        Schema::dropIfExists('residents');
    }

    public function down(): void
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('finance_officers')) {
            Schema::create('finance_officers', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('residents')) {
            Schema::create('residents', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->string('payment_behavior')->default('Not available yet');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'payment_behavior')) {
                $table->dropColumn('payment_behavior');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
