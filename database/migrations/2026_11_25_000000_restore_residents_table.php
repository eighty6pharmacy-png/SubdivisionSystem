<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('residents')) {
            Schema::create('residents', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->string('payment_behavior', 100)->default('Not available yet');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // 1. Backfill all users with role = 'Resident'
        if (Schema::hasTable('users')) {
            $hasBehaviorCol = Schema::hasColumn('users', 'payment_behavior');
            $residentUsers = DB::table('users')
                ->where('role', 'Resident')
                ->select($hasBehaviorCol ? ['id', 'payment_behavior'] : ['id'])
                ->get();

            $now = now();
            foreach ($residentUsers as $user) {
                DB::table('residents')->insertOrIgnore([
                    'user_id' => $user->id,
                    'payment_behavior' => ($hasBehaviorCol && ! empty($user->payment_behavior))
                        ? $user->payment_behavior
                        : 'Not available yet',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Ensure any user referenced as resident_id in child tables also has a residents row
        foreach (['resident_lots', 'announcement_recipients', 'utility_bills', 'incidents', 'visitors'] as $childTable) {
            if (Schema::hasTable($childTable) && Schema::hasColumn($childTable, 'resident_id')) {
                $ids = DB::table($childTable)
                    ->whereNotNull('resident_id')
                    ->distinct()
                    ->pluck('resident_id');

                $now = now();
                foreach ($ids as $uid) {
                    if (DB::table('users')->where('id', $uid)->exists()) {
                        DB::table('residents')->insertOrIgnore([
                            'user_id' => $uid,
                            'payment_behavior' => 'Not available yet',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        // 3. Retarget foreign keys from users(id) onto residents(user_id)
        foreach (['resident_lots', 'announcement_recipients'] as $cascadeTable) {
            if (Schema::hasTable($cascadeTable) && Schema::hasColumn($cascadeTable, 'resident_id')) {
                Schema::table($cascadeTable, function (Blueprint $table) {
                    $table->dropForeign(['resident_id']);
                    $table->foreign('resident_id')->references('user_id')->on('residents')->cascadeOnDelete();
                });
            }
        }

        foreach (['utility_bills', 'incidents', 'visitors'] as $nullTable) {
            if (Schema::hasTable($nullTable) && Schema::hasColumn($nullTable, 'resident_id')) {
                Schema::table($nullTable, function (Blueprint $table) {
                    $table->dropForeign(['resident_id']);
                    $table->foreign('resident_id')->references('user_id')->on('residents')->nullOnDelete();
                });
            }
        }

        // 4. Drop payment_behavior from users
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'payment_behavior')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('payment_behavior');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'payment_behavior')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('payment_behavior', 100)->default('Not available yet')->after('role');
            });
        }

        if (Schema::hasTable('residents') && Schema::hasColumn('users', 'payment_behavior')) {
            $residents = DB::table('residents')->select('user_id', 'payment_behavior')->get();
            foreach ($residents as $resident) {
                DB::table('users')
                    ->where('id', $resident->user_id)
                    ->update(['payment_behavior' => $resident->payment_behavior ?: 'Not available yet']);
            }
        }

        foreach (['resident_lots', 'announcement_recipients'] as $cascadeTable) {
            if (Schema::hasTable($cascadeTable) && Schema::hasColumn($cascadeTable, 'resident_id')) {
                Schema::table($cascadeTable, function (Blueprint $table) {
                    $table->dropForeign(['resident_id']);
                    $table->foreign('resident_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        foreach (['utility_bills', 'incidents', 'visitors'] as $nullTable) {
            if (Schema::hasTable($nullTable) && Schema::hasColumn($nullTable, 'resident_id')) {
                Schema::table($nullTable, function (Blueprint $table) {
                    $table->dropForeign(['resident_id']);
                    $table->foreign('resident_id')->references('id')->on('users')->nullOnDelete();
                });
            }
        }

        Schema::dropIfExists('residents');
    }
};
