<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Splits login identity onto users and role-specific rows onto admins,
 * finance_officers, guards, residents, and buyers. Does not edit older migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addNameColumnsAndBackfill();
        $this->makePasswordNullable();
        $this->createRoleTables();
        $this->migrateBuyerMasterLists();
        $this->backfillRoleRows();
        $this->retargetBuyerForeignKeys();
        $this->moveLots();
        $this->retargetResidentAndGuardForeignKeys();
        $this->backfillAnnouncements();
        $this->dropLegacyUserColumnsAndMasterList();
        $this->restorePartialUniqueIndexes();
    }

    public function down(): void
    {
        throw new \RuntimeException('The account split cannot be reversed without data loss. Restore from a backup.');
    }

    private function addNameColumnsAndBackfill(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
        });

        DB::table('users')->orderBy('id')->lazy()->each(function ($user) {
            $parts = $this->splitName($user->name ?? '');
            DB::table('users')->where('id', $user->id)->update($parts);
        });
    }

    private function makePasswordNullable(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    private function createRoleTables(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('finance_officers', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('guards', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('residents', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('payment_behavior')->default('Not available yet');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('buyers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('civil_status')->nullable();
            $table->string('spouse_name')->nullable();
            $table->text('present_address')->nullable();
            $table->text('postal_address')->nullable();
            $table->string('proof_of_id')->nullable();
            $table->string('pagibig_number')->nullable();
            $table->string('financing_method')->nullable();
            $table->uuid('lot_id')->nullable();
            $table->text('lot_link_warning')->nullable();
            $table->string('tct_no')->nullable();
            $table->string('pid')->nullable();
            $table->string('tax_dec_no')->nullable();
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->decimal('contract_amount', 15, 2)->nullable();
            $table->decimal('improvement_amount', 15, 2)->nullable();
            $table->decimal('equity', 15, 2)->nullable();
            $table->decimal('loan_base', 15, 2)->nullable();
            $table->integer('loan_term')->nullable();
            $table->decimal('monthly_amortization', 15, 2)->nullable();
            $table->decimal('mri_sri', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('mri_ds_1time', 15, 2)->nullable();
            $table->decimal('nonlife_1time', 15, 2)->nullable();
            $table->decimal('retention', 15, 2)->nullable();
            $table->decimal('total_deductions', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('lot_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->restrictOnDelete();
        });

        Schema::create('resident_lots', function (Blueprint $table) {
            $table->uuid('resident_id');
            $table->uuid('lot_id');
            $table->timestamps();
            $table->primary(['resident_id', 'lot_id']);
            $table->index('lot_id');
            $table->foreign('resident_id')->references('user_id')->on('residents')->cascadeOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->cascadeOnDelete();
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->uuid('announcement_id');
            $table->uuid('resident_id');
            $table->timestamps();
            $table->primary(['announcement_id', 'resident_id']);
            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('resident_id')->references('user_id')->on('residents')->cascadeOnDelete();
        });
    }

    private function migrateBuyerMasterLists(): void
    {
        $contractColumns = [
            'civil_status', 'spouse_name', 'present_address', 'postal_address', 'proof_of_id',
            'pagibig_number', 'financing_method', 'tct_no', 'pid',
            'tax_dec_no', 'lot_area', 'floor_area', 'description', 'contract_amount', 'improvement_amount',
            'equity', 'loan_base', 'loan_term', 'monthly_amortization', 'mri_sri', 'insurance',
            'mri_ds_1time', 'nonlife_1time', 'retention', 'total_deductions', 'created_at', 'updated_at', 'deleted_at',
        ];

        $rows = DB::table('buyer_master_lists')->orderBy('created_at')->get();
        foreach ($rows as $row) {
            $linked = DB::table('users')->where('buyer_master_list_id', $row->id)->first();
            if ($linked) {
                $userId = $linked->id;
                $emailUpdate = [];
                if (! empty($row->email)) {
                    $taken = DB::table('users')
                        ->where('email', $row->email)
                        ->where('id', '!=', $linked->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if (! $taken) {
                        $emailUpdate['email'] = $row->email;
                    }
                }
                DB::table('users')->where('id', $userId)->update(array_merge([
                    'first_name' => $row->first_name,
                    'middle_name' => $row->middle_name,
                    'last_name' => $row->last_name,
                    'contact_number' => $row->contact_number ?: $linked->contact_number,
                    'updated_at' => now(),
                ], $emailUpdate));
            } else {
                $userId = (string) Str::uuid();
                $email = $row->email;
                $emailTaken = $email && DB::table('users')->where('email', $email)->whereNull('deleted_at')->exists();
                if (! $email || $emailTaken) {
                    $email = 'buyer-'.$row->id.'@buyers.invalid';
                    Log::warning('Buyer '.$row->id.' had no usable email. A placeholder was stored because users.email cannot be empty. Replace it with a real address before generating a login.');
                }
                $displayName = trim(implode(' ', array_filter(
                    [$row->first_name, $row->middle_name, $row->last_name],
                    fn ($part) => $part !== null && $part !== ''
                )));
                DB::table('users')->insert([
                    'id' => $userId,
                    'name' => $displayName !== '' ? $displayName : 'Buyer',
                    'first_name' => $row->first_name,
                    'middle_name' => $row->middle_name,
                    'last_name' => $row->last_name,
                    'email' => $email,
                    'contact_number' => $row->contact_number,
                    'password' => null,
                    'status' => $row->deleted_at ? 'Archived' : 'Active',
                    'joined_at' => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at,
                ]);
                $this->attachBuyerRole($userId);
            }

            $lotId = $row->lot_id;
            if (! $lotId && $row->block_no !== null && $row->block_no !== '' && $row->lot_no !== null && $row->lot_no !== '') {
                $lotId = DB::table('lots')
                    ->where('block', (string) $row->block_no)
                    ->where('lot_number', (string) $row->lot_no)
                    ->value('id');
            }

            $buyerRow = [
                'id' => $row->id,
                'user_id' => $userId,
                'lot_id' => $lotId,
                'lot_link_warning' => null,
            ];
            foreach ($contractColumns as $column) {
                $buyerRow[$column] = $row->{$column};
            }
            DB::table('buyers')->insert($buyerRow);
        }
    }

    private function attachBuyerRole(string $userId): void
    {
        $roleId = DB::table('roles')->where('name', 'Buyer')->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Buyer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exists = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->exists();
        if ($exists) {
            return;
        }

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $userId,
        ]);
    }

    private function backfillRoleRows(): void
    {
        $tables = [
            'Admin' => 'admins',
            'Finance Officer' => 'finance_officers',
            'Security Guard' => 'guards',
            'Resident' => 'residents',
            'Buyer' => 'buyers',
        ];

        $assignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->select('model_has_roles.model_id', 'roles.name')
            ->get();

        foreach ($assignments as $assignment) {
            $table = $tables[$assignment->name] ?? null;
            if (! $table) {
                continue;
            }
            if (DB::table($table)->where('user_id', $assignment->model_id)->exists()) {
                if ($table === 'residents') {
                    $behavior = DB::table('users')->where('id', $assignment->model_id)->value('payment_behavior');
                    DB::table('residents')->where('user_id', $assignment->model_id)->update([
                        'payment_behavior' => $behavior ?: 'Not available yet',
                    ]);
                }
                continue;
            }

            $insert = [
                'user_id' => $assignment->model_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($table === 'residents') {
                $behavior = DB::table('users')->where('id', $assignment->model_id)->value('payment_behavior');
                $insert['payment_behavior'] = $behavior ?: 'Not available yet';
            }
            if ($table === 'buyers') {
                $insert['id'] = (string) Str::uuid();
            }
            DB::table($table)->insert($insert);
        }
    }

    private function retargetBuyerForeignKeys(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->uuid('buyer_id')->nullable()->after('lot_id');
        });
        Schema::table('financing_records', function (Blueprint $table) {
            $table->uuid('buyer_id')->nullable()->after('id');
        });

        $buyerIds = DB::table('buyers')->pluck('id')->flip();

        foreach (DB::table('reservations')->whereNotNull('buyer_master_list_id')->get() as $reservation) {
            $buyerId = $buyerIds->has($reservation->buyer_master_list_id)
                ? $reservation->buyer_master_list_id
                : null;
            DB::table('reservations')->where('id', $reservation->id)->update(['buyer_id' => $buyerId]);
        }

        foreach (DB::table('financing_records')->get() as $record) {
            if (! $buyerIds->has($record->buyer_master_list_id)) {
                throw new \RuntimeException('Financing record '.$record->id.' has no buyer to map onto buyers.id.');
            }
            DB::table('financing_records')->where('id', $record->id)->update([
                'buyer_id' => $record->buyer_master_list_id,
            ]);
        }

        DB::statement('DROP INDEX IF EXISTS financing_records_buyer_master_list_id_unique');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['buyer_master_list_id']);
        });
        DB::statement('DROP INDEX IF EXISTS reservations_buyer_master_list_id_index');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('buyer_master_list_id');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('buyer_id');
            $table->foreign('buyer_id')->references('id')->on('buyers')->restrictOnDelete();
        });

        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropForeign(['buyer_master_list_id']);
        });
        DB::statement('DROP INDEX IF EXISTS financing_records_buyer_master_list_id_index');
        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropColumn('buyer_master_list_id');
        });
        Schema::table('financing_records', function (Blueprint $table) {
            $table->index('buyer_id');
            $table->unique('buyer_id', 'financing_records_buyer_id_unique');
            $table->foreign('buyer_id')->references('id')->on('buyers')->restrictOnDelete();
        });
    }

    private function moveLots(): void
    {
        $grouped = DB::table('user_lots')->get()->groupBy('user_id');
        foreach ($grouped as $userId => $rows) {
            if (DB::table('residents')->where('user_id', $userId)->exists()) {
                foreach ($rows as $row) {
                    DB::table('resident_lots')->insert([
                        'resident_id' => $row->user_id,
                        'lot_id' => $row->lot_id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
                continue;
            }

            $buyer = DB::table('buyers')->where('user_id', $userId)->first();
            if (! $buyer) {
                continue;
            }

            $lotIds = $rows->pluck('lot_id')->unique()->values();
            $kept = $buyer->lot_id;
            if ($kept === null && $lotIds->count() === 1) {
                $kept = $lotIds->first();
                DB::table('buyers')->where('id', $buyer->id)->update([
                    'lot_id' => $kept,
                    'updated_at' => now(),
                ]);
            }

            $dropped = $lotIds->filter(fn ($id) => $id !== $kept)->values();
            if ($dropped->isEmpty()) {
                continue;
            }

            $labels = DB::table('lots')->whereIn('id', $dropped->all())->get()
                ->map(fn ($lot) => 'Block '.$lot->block.' Lot '.$lot->lot_number)
                ->implode(', ');
            DB::table('buyers')->where('id', $buyer->id)->update([
                'lot_link_warning' => 'An extra lot link was not kept: '.$labels.'. The contract uses the lot stored on this buyer. A contract-only lot does not get a utility bill until a resident occupies it.',
                'updated_at' => now(),
            ]);
        }

        Schema::drop('user_lots');
    }

    private function retargetResidentAndGuardForeignKeys(): void
    {
        $this->snapshotAndRename('utility_bills', 'user_id', 'resident_id', 'billed_to_name', 'residents');
        $this->snapshotAndRename('incidents', 'user_id', 'resident_id', 'reporter_name', 'residents');
        $this->snapshotAndRename('visitors', 'host_id', 'resident_id', 'host_name', 'residents');
        $this->snapshotGuardLinks();
    }

    private function snapshotAndRename(string $table, string $oldColumn, string $newColumn, string $snapshotColumn, string $targetTable): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($snapshotColumn) {
            $blueprint->string($snapshotColumn)->nullable();
        });

        Schema::table($table, function (Blueprint $blueprint) use ($oldColumn) {
            $blueprint->dropForeign([$oldColumn]);
        });

        foreach (DB::table($table)->whereNotNull($oldColumn)->get() as $row) {
            $user = DB::table('users')->where('id', $row->{$oldColumn})->first();
            $isValid = DB::table($targetTable)->where('user_id', $row->{$oldColumn})->exists();
            DB::table($table)->where('id', $row->id)->update([
                $snapshotColumn => $user ? $this->personName($user) : null,
                $oldColumn => $isValid ? $row->{$oldColumn} : null,
            ]);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($oldColumn, $newColumn) {
            $blueprint->renameColumn($oldColumn, $newColumn);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($newColumn, $targetTable) {
            $blueprint->foreign($newColumn)->references('user_id')->on($targetTable)->nullOnDelete();
        });
    }

    private function snapshotGuardLinks(): void
    {
        Schema::table('visitors', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['guard_id']);
        });

        foreach (DB::table('visitors')->whereNotNull('guard_id')->get() as $row) {
            $isGuard = DB::table('guards')->where('user_id', $row->guard_id)->exists();
            if ($isGuard) {
                continue;
            }

            $updates = ['guard_id' => null];
            if (blank($row->guard_name)) {
                $user = DB::table('users')->where('id', $row->guard_id)->first();
                $updates['guard_name'] = $user ? $this->personName($user) : null;
            }
            DB::table('visitors')->where('id', $row->id)->update($updates);
        }

        Schema::table('visitors', function (Blueprint $blueprint) {
            $blueprint->foreign('guard_id')->references('user_id')->on('guards')->nullOnDelete();
        });
    }

    private function personName(object $user): string
    {
        return trim(implode(' ', array_filter(
            [$user->first_name ?? null, $user->middle_name ?? null, $user->last_name ?? null],
            fn ($part) => $part !== null && $part !== ''
        )));
    }

    private function backfillAnnouncements(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->uuid('admin_id')->nullable()->after('author');
            $table->foreign('admin_id')->references('user_id')->on('admins')->nullOnDelete();
        });

        $admins = DB::table('admins')
            ->join('users', 'users.id', '=', 'admins.user_id')
            ->get(['admins.user_id', 'users.first_name', 'users.middle_name', 'users.last_name']);

        foreach (DB::table('announcements')->get() as $announcement) {
            $matched = null;
            foreach ($admins as $admin) {
                $name = $this->personName($admin);
                if ($name !== '' && $name === $announcement->author) {
                    $matched = $admin->user_id;
                    break;
                }
            }
            if ($matched) {
                DB::table('announcements')->where('id', $announcement->id)->update([
                    'admin_id' => $matched,
                ]);
            }
        }
    }

    private function dropLegacyUserColumnsAndMasterList(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_buyer_master_list_id_unique');
        DB::statement('DROP INDEX IF EXISTS users_buyer_master_list_id_index');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['buyer_master_list_id']);
        });
        DB::statement('DROP INDEX IF EXISTS users_buyer_master_list_id_index');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['buyer_master_list_id', 'payment_behavior', 'name']);
        });

        Schema::drop('buyer_master_lists');
    }

    /**
     * SQLite rebuilds a table when a column or foreign key changes and drops the WHERE
     * clause from partial unique indexes. Put those indexes back on both drivers.
     */
    private function restorePartialUniqueIndexes(): void
    {
        $indexes = [
            'users_email_active_unique' => 'users (email) WHERE deleted_at IS NULL',
            'users_external_id_active_unique' => 'users (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL',
            'reservations_active_lot_unique' => "reservations (lot_id) WHERE status IN ('Pending', 'Reserved', 'Converted') AND deleted_at IS NULL",
            'downpayments_reservation_id_unique' => 'downpayments (reservation_id) WHERE deleted_at IS NULL',
            'utility_bills_lot_type_period_unique' => 'utility_bills (lot_id, type, billing_period) WHERE deleted_at IS NULL',
            'utility_bills_paymongo_checkout_id_unique' => 'utility_bills (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
            'downpayments_paymongo_checkout_id_unique' => 'downpayments (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
            'financing_records_paymongo_checkout_id_unique' => 'financing_records (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
            'payments_paymongo_payment_id_unique' => 'payments (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
            'downpayment_histories_paymongo_payment_id_unique' => 'downpayment_histories (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
            'financing_payment_histories_paymongo_payment_id_unique' => 'financing_payment_histories (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
            'financing_payment_histories_trn_unique' => 'financing_payment_histories (trn) WHERE trn IS NOT NULL',
        ];

        foreach ($indexes as $name => $definition) {
            DB::statement('DROP INDEX IF EXISTS '.$name);
            DB::statement('CREATE UNIQUE INDEX '.$name.' ON '.$definition);
        }
    }

    /**
     * @return array{first_name: string, middle_name: ?string, last_name: ?string}
     */
    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) <= 1) {
            return [
                'first_name' => $parts[0] ?? '',
                'middle_name' => null,
                'last_name' => null,
            ];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return [
            'first_name' => $first,
            'middle_name' => $parts !== [] ? implode(' ', $parts) : null,
            'last_name' => $last,
        ];
    }
};
