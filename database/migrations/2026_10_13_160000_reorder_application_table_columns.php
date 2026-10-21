<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts each application table into a formal column order:
 * primary key, foreign keys, other columns (last name before first name), then timestamps.
 *
 * Types, defaults, rows, primary keys, checks, indexes, and foreign keys are copied.
 * Laravel framework tables are left alone. Already-ordered tables are skipped.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * @var array<string, list<string>>
     */
    private array $orders = [
        'users' => [
            'id', 'last_name', 'first_name', 'middle_name', 'external_id', 'email', 'contact_number',
            'email_verified_at', 'password', 'status', 'joined_at', 'remember_token',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'admins' => ['user_id', 'created_at', 'updated_at'],
        'finance_officers' => ['user_id', 'created_at', 'updated_at'],
        'guards' => ['user_id', 'schedule_days', 'shift_type', 'shift_start', 'shift_end', 'created_at', 'updated_at'],
        'residents' => ['user_id', 'payment_behavior', 'created_at', 'updated_at'],
        'buyers' => [
            'id', 'user_id', 'lot_id', 'civil_status', 'spouse_name', 'present_address', 'postal_address',
            'id_type', 'proof_of_id', 'pagibig_number', 'lot_link_warning', 'tct_no', 'pid', 'tax_dec_no',
            'lot_area', 'floor_area', 'description', 'created_at', 'updated_at', 'deleted_at',
        ],
        'lots' => [
            'id', 'block', 'lot_number', 'status', 'provider_managed',
            'lot_area', 'floor_area', 'description', 'tct_no', 'pid', 'tax_dec_no', 'contract_price',
            'created_at', 'updated_at',
        ],
        'resident_lots' => ['resident_id', 'lot_id', 'created_at', 'updated_at'],
        'reservations' => [
            'id', 'user_id', 'lot_id', 'buyer_id', 'last_name', 'first_name', 'status',
            'reservation_date', 'deadline_date', 'amount', 'notes', 'created_at', 'updated_at', 'deleted_at',
        ],
        'downpayments' => [
            'id', 'reservation_id', 'amount', 'due_date', 'status', 'balance', 'monthly_amortization',
            'months_to_pay', 'contract_date', 'penalty_percentage', 'paymongo_checkout_id',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'downpayment_histories' => [
            'id', 'downpayment_id', 'amount', 'payment_date', 'trn', 'payment_method',
            'paymongo_payment_id', 'status', 'created_at', 'updated_at',
        ],
        'financing_records' => [
            'id', 'buyer_id', 'lot_id', 'financing_method', 'loan_base', 'loan_term', 'monthly_amortization',
            'contract_price', 'mbrdc_add_fees', 'other_exp_annotation', 'improvements_fee', 'inspection_fee',
            'retention_fee', 'total_amt_due', 'mbrdc_turn_over_fee', 'total_consideration', 'paid_by_vendee_equity',
            'mbrdc_amt_due_for_financing', 'additional_bill_of_materials', 'loan_release', 'net_loan_proceeds',
            'receivables', 'amount_paid', 'balance', 'rbgi_ap', 'mbrdc_ap_in_house', 'account_receivables',
            'remarks', 'status', 'loan_release_date', 'sri_mri', 'pag_ibig_non_life', 'interim_mri',
            'retention_on_hold', 'paymongo_checkout_id', 'created_at', 'updated_at',
        ],
        'financing_payment_histories' => [
            'id', 'financing_record_id', 'amount_paid', 'payment_date', 'trn', 'payment_method',
            'paymongo_payment_id', 'status', 'created_at', 'updated_at',
        ],
        'utility_bills' => [
            'id', 'resident_id', 'lot_id', 'billed_to_name', 'type', 'billing_period',
            'previous_reading', 'previous_reading_date', 'current_reading', 'current_reading_date',
            'usage_value', 'amount', 'previous_balance', 'due_date', 'status', 'is_at_risk',
            'paymongo_checkout_id', 'paymongo_checkout_url', 'created_at', 'updated_at', 'deleted_at',
        ],
        'payments' => [
            'id', 'utility_bill_id', 'amount_paid', 'method', 'trn', 'paymongo_payment_id',
            'payment_date', 'created_at', 'updated_at', 'deleted_at',
        ],
        'incidents' => [
            'id', 'resident_id', 'reporter_name', 'subject', 'type', 'description', 'image_url', 'video_url', 'admin_notes',
            'status', 'created_at', 'updated_at', 'deleted_at',
        ],
        'visitors' => [
            'id', 'resident_id', 'guard_id', 'host_name', 'guard_name', 'visitor_name', 'visitor_address',
            'contact_number', 'pin', 'access_token', 'purpose', 'validity', 'arrival_time', 'status', 'plate_number', 'type',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'announcements' => [
            'id', 'admin_id', 'title', 'category', 'content', 'author', 'status', 'publish_date',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'announcement_recipients' => ['announcement_id', 'resident_id', 'created_at', 'updated_at'],
        'appointments' => [
            'id', 'last_name', 'first_name', 'middle_name', 'contact_number', 'email', 'type', 'date',
            'time', 'notes', 'report', 'status', 'pin', 'created_at', 'updated_at', 'deleted_at',
        ],
        'settings' => ['key', 'value', 'created_at', 'updated_at'],
    ];

    public function up(): void
    {
        // Older databases created this table before payment_method was added to the
        // create migration, so the column was never applied. Add it before reordering.
        if (Schema::hasTable('financing_payment_histories')
            && ! Schema::hasColumn('financing_payment_histories', 'payment_method')) {
            Schema::table('financing_payment_histories', function (Blueprint $table) {
                $table->string('payment_method')->nullable()->default('Office Payment');
            });
        }

        if (Schema::hasTable('guards')
            && ! Schema::hasColumn('guards', 'schedule_days')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->json('schedule_days')->nullable();
                $table->string('shift_type')->nullable()->default('day');
                $table->time('shift_start')->nullable()->default('06:00:00');
                $table->time('shift_end')->nullable()->default('18:00:00');
            });
        }

        if (Schema::hasTable('visitors')
            && ! Schema::hasColumn('visitors', 'contact_number')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->string('contact_number', 30)->nullable();
                $table->string('access_token', 64)->nullable()->unique();
            });
        }

        if (Schema::hasTable('incidents')
            && ! Schema::hasColumn('incidents', 'video_url')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->text('video_url')->nullable();
                $table->text('admin_notes')->nullable();
            });
        }

        if (Schema::hasTable('lots')
            && ! Schema::hasColumn('lots', 'lot_area')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->decimal('lot_area', 10, 2)->nullable();
                $table->decimal('floor_area', 10, 2)->nullable();
                $table->text('description')->nullable();
                $table->string('tct_no')->nullable();
                $table->string('pid')->nullable();
                $table->string('tax_dec_no')->nullable();
                $table->decimal('contract_price', 12, 2)->nullable();
            });
        }

        if (Schema::hasTable('lots')
            && ! Schema::hasColumn('lots', 'contract_price')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->decimal('contract_price', 12, 2)->nullable();
            });
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $this->reorderPostgres();

            return;
        }

        if ($driver === 'sqlite') {
            $this->reorderSqlite();

            return;
        }

        throw new RuntimeException('Column reorder supports PostgreSQL and SQLite. This connection is '.$driver.'.');
    }

    public function down(): void
    {
        throw new RuntimeException('Column order is not reversed automatically. Restore from a backup to undo it.');
    }

    private function reorderPostgres(): void
    {
        $plans = [];
        foreach ($this->orders as $table => $desired) {
            $this->assertTable($table);
            $columns = $this->postgresColumns($table);
            $names = array_column($columns, 'name');
            if ($names === $desired) {
                continue;
            }
            $this->assertColumnSet($table, $names, $desired);
            $plans[$table] = [
                'count' => (int) DB::table($table)->count(),
                'columns' => $columns,
            ];
        }

        if ($plans === []) {
            return;
        }

        $rebuilt = array_keys($plans);
        $this->assertNoDependents($rebuilt);
        $foreignKeys = $this->postgresForeignKeys($rebuilt);
        $checks = $this->postgresChecks($rebuilt);
        $indexes = $this->postgresSecondaryIndexes($rebuilt);
        $primaryKeys = $this->postgresPrimaryKeys($rebuilt);
        $before = $this->postgresSafetyCounts($rebuilt);

        DB::beginTransaction();
        try {
            foreach ($foreignKeys as $foreignKey) {
                DB::statement(
                    'ALTER TABLE '.$this->quote($foreignKey['table'])
                    .' DROP CONSTRAINT '.$this->quote($foreignKey['name'])
                );
            }

            foreach ($plans as $table => $plan) {
                $this->rebuildPostgresTable(
                    $table,
                    $plan['columns'],
                    $plan['count'],
                    $primaryKeys[$table],
                    $checks[$table] ?? [],
                    $indexes[$table] ?? []
                );
            }

            foreach ($foreignKeys as $foreignKey) {
                DB::statement(
                    'ALTER TABLE '.$this->quote($foreignKey['table'])
                    .' ADD CONSTRAINT '.$this->quote($foreignKey['name']).' '.$foreignKey['definition']
                );
            }

            $after = $this->postgresSafetyCounts($rebuilt);
            if ($before !== $after) {
                throw new RuntimeException('Column reorder changed keys or indexes. Before '
                    .json_encode($before).' after '.json_encode($after).'.');
            }

            foreach ($plans as $table => $plan) {
                $names = array_column($this->postgresColumns($table), 'name');
                if ($names !== $this->orders[$table]) {
                    throw new RuntimeException($table.' column order was not applied.');
                }
                $copied = (int) DB::table($table)->count();
                if ($copied !== $plan['count']) {
                    throw new RuntimeException($table.' row count changed during column reorder.');
                }
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param  list<array{name: string, type: string, not_null: bool, default: ?string}>  $columns
     * @param  array{name: string, definition: string}  $primaryKey
     * @param  list<array{name: string, definition: string}>  $checks
     * @param  list<array{name: string, definition: string}>  $indexes
     */
    private function rebuildPostgresTable(string $table, array $columns, int $count, array $primaryKey, array $checks, array $indexes): void
    {
        $byName = [];
        foreach ($columns as $column) {
            $byName[$column['name']] = $column;
        }

        $temporary = $table.'__ordered';
        $definitions = [];
        $quotedNames = [];
        foreach ($this->orders[$table] as $name) {
            $column = $byName[$name];
            $definition = $this->quote($name).' '.$column['type'].($column['not_null'] ? ' NOT NULL' : ' NULL');
            if ($column['default'] !== null) {
                $definition .= ' DEFAULT '.$column['default'];
            }
            $definitions[] = $definition;
            $quotedNames[] = $this->quote($name);
        }

        $sequence = $this->postgresSequence($table);
        if ($sequence !== null) {
            DB::statement('ALTER SEQUENCE '.$this->quoteQualified($sequence).' OWNED BY NONE');
        }

        DB::statement('CREATE TABLE '.$this->quote($temporary).' ('.implode(', ', $definitions).')');
        $columnList = implode(', ', $quotedNames);
        DB::statement(
            'INSERT INTO '.$this->quote($temporary).' ('.$columnList.') SELECT '.$columnList.' FROM '.$this->quote($table)
        );

        $copied = (int) DB::table($temporary)->count();
        if ($copied !== $count) {
            throw new RuntimeException($table.' copy has '.$copied.' rows; the original has '.$count.'.');
        }

        DB::statement('DROP TABLE '.$this->quote($table));
        DB::statement('ALTER TABLE '.$this->quote($temporary).' RENAME TO '.$this->quote($table));

        if ($sequence !== null) {
            DB::statement(
                'ALTER SEQUENCE '.$this->quoteQualified($sequence)
                .' OWNED BY '.$this->quote($table).'.'.$this->quote('id')
            );
            DB::statement(
                'SELECT setval('.$this->quoteLiteral($sequence).', '
                .'COALESCE((SELECT MAX('.$this->quote('id').') FROM '.$this->quote($table).'), 1), '
                .'(SELECT COUNT(*) FROM '.$this->quote($table).') > 0)'
            );
        }

        DB::statement(
            'ALTER TABLE '.$this->quote($table)
            .' ADD CONSTRAINT '.$this->quote($primaryKey['name']).' '.$primaryKey['definition']
        );

        foreach ($checks as $check) {
            DB::statement(
                'ALTER TABLE '.$this->quote($table)
                .' ADD CONSTRAINT '.$this->quote($check['name']).' '.$check['definition']
            );
        }

        foreach ($indexes as $index) {
            DB::statement($index['definition']);
        }
    }

    private function reorderSqlite(): void
    {
        $pdo = DB::connection()->getPdo();
        $pdo->exec('PRAGMA foreign_keys = OFF');

        try {
            DB::beginTransaction();
            foreach ($this->orders as $table => $desired) {
                $this->assertTable($table);
                $this->rebuildSqliteTable($table, $desired);
            }
            $problems = DB::select('PRAGMA foreign_key_check');
            if ($problems !== []) {
                throw new RuntimeException('Foreign key check failed after column reorder.');
            }
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        } finally {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * @param  list<string>  $desired
     */
    private function rebuildSqliteTable(string $table, array $desired): void
    {
        $columns = DB::select('PRAGMA table_info('.$this->quote($table).')');
        $names = array_map(fn ($column) => $column->name, $columns);
        if ($names === $desired) {
            return;
        }
        $this->assertColumnSet($table, $names, $desired);

        $byName = [];
        $primaryKey = [];
        foreach ($columns as $column) {
            $byName[$column->name] = $column;
            if ((int) $column->pk > 0) {
                $primaryKey[(int) $column->pk] = $column->name;
            }
        }
        ksort($primaryKey);
        $primaryKey = array_values($primaryKey);

        $createSql = (string) (DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table]
        )->sql ?? '');
        $autoIncrement = count($primaryKey) === 1
            && $primaryKey[0] === 'id'
            && preg_match('/\bautoincrement\b/i', $createSql) === 1;

        $foreignKeys = $this->sqliteForeignKeys($table);
        $indexes = DB::select(
            "SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql IS NOT NULL",
            [$table]
        );
        $before = (int) DB::table($table)->count();
        $temporary = $table.'__ordered';

        $definitions = [];
        foreach ($desired as $name) {
            $definitions[] = $this->sqliteColumnDefinition($byName[$name], $primaryKey, $autoIncrement);
        }
        if (count($primaryKey) > 1) {
            $definitions[] = 'PRIMARY KEY ('.implode(', ', array_map(fn (string $name) => $this->quote($name), $primaryKey)).')';
        }
        foreach ($foreignKeys as $foreignKey) {
            $definitions[] = $foreignKey;
        }

        DB::statement('CREATE TABLE '.$this->quote($temporary).' ('.implode(', ', $definitions).')');
        $columnList = implode(', ', array_map(fn (string $name) => $this->quote($name), $desired));
        DB::statement(
            'INSERT INTO '.$this->quote($temporary).' ('.$columnList.') SELECT '.$columnList.' FROM '.$this->quote($table)
        );

        $copied = (int) DB::table($temporary)->count();
        if ($copied !== $before) {
            throw new RuntimeException($table.' copy has '.$copied.' rows; the original has '.$before.'.');
        }

        DB::statement('DROP TABLE '.$this->quote($table));
        DB::statement('ALTER TABLE '.$this->quote($temporary).' RENAME TO '.$this->quote($table));

        foreach ($indexes as $index) {
            DB::statement($index->sql);
        }

        $names = array_map(
            fn ($column) => $column->name,
            DB::select('PRAGMA table_info('.$this->quote($table).')')
        );
        if ($names !== $desired) {
            throw new RuntimeException($table.' column order was not applied.');
        }
    }

    private function sqliteColumnDefinition(object $column, array $primaryKey, bool $autoIncrement): string
    {
        $singlePrimary = count($primaryKey) === 1 && $primaryKey[0] === $column->name;
        $type = $column->type !== '' ? $column->type : 'TEXT';
        if ($singlePrimary && $autoIncrement) {
            $type = 'INTEGER';
        }

        $sql = $this->quote($column->name).' '.$type;
        if ($singlePrimary && $autoIncrement) {
            $sql .= ' PRIMARY KEY AUTOINCREMENT';
        } elseif ((int) $column->notnull === 1) {
            $sql .= ' NOT NULL';
        }
        if ($column->dflt_value !== null) {
            $sql .= ' DEFAULT '.$column->dflt_value;
        }
        if ($singlePrimary && ! $autoIncrement) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    /**
     * @return list<string>
     */
    private function sqliteForeignKeys(string $table): array
    {
        $groups = [];
        foreach (DB::select('PRAGMA foreign_key_list('.$this->quote($table).')') as $foreignKey) {
            $groups[$foreignKey->id]['table'] = $foreignKey->table;
            $groups[$foreignKey->id]['on_update'] = $foreignKey->on_update;
            $groups[$foreignKey->id]['on_delete'] = $foreignKey->on_delete;
            $groups[$foreignKey->id]['from'][(int) $foreignKey->seq] = $foreignKey->from;
            $groups[$foreignKey->id]['to'][(int) $foreignKey->seq] = $foreignKey->to;
        }

        $definitions = [];
        foreach ($groups as $foreignKey) {
            ksort($foreignKey['from']);
            ksort($foreignKey['to']);
            $from = implode(', ', array_map(fn (string $name) => $this->quote($name), $foreignKey['from']));
            $to = implode(', ', array_map(fn (string $name) => $this->quote($name), $foreignKey['to']));
            $onUpdate = $this->sqliteAction($foreignKey['on_update']);
            $onDelete = $this->sqliteAction($foreignKey['on_delete']);
            $definitions[] = 'FOREIGN KEY ('.$from.') REFERENCES '.$this->quote($foreignKey['table']).' ('.$to.')'
                .' ON UPDATE '.$onUpdate.' ON DELETE '.$onDelete;
        }

        return $definitions;
    }

    private function sqliteAction(string $action): string
    {
        $action = strtoupper($action);
        $allowed = ['NO ACTION', 'RESTRICT', 'CASCADE', 'SET NULL', 'SET DEFAULT'];
        if (! in_array($action, $allowed, true)) {
            throw new RuntimeException('Unsupported foreign key action '.$action.'.');
        }

        return $action;
    }

    /**
     * @return list<array{name: string, type: string, not_null: bool, default: ?string}>
     */
    private function postgresColumns(string $table): array
    {
        $rows = DB::select(
            'SELECT a.attname AS name,
                    pg_catalog.format_type(a.atttypid, a.atttypmod) AS type,
                    a.attnotnull AS not_null,
                    pg_get_expr(ad.adbin, ad.adrelid) AS default_expr
             FROM pg_attribute a
             JOIN pg_class c ON c.oid = a.attrelid
             JOIN pg_namespace n ON n.oid = c.relnamespace
             LEFT JOIN pg_attrdef ad ON ad.adrelid = a.attrelid AND ad.adnum = a.attnum
             WHERE n.nspname = current_schema()
               AND c.relname = ?
               AND a.attnum > 0
               AND NOT a.attisdropped
             ORDER BY a.attnum',
            [$table]
        );

        $columns = [];
        foreach ($rows as $row) {
            $columns[] = [
                'name' => $row->name,
                'type' => $row->type,
                'not_null' => $this->isTrue($row->not_null),
                'default' => $row->default_expr,
            ];
        }

        return $columns;
    }

    /**
     * @param  list<string>  $tables
     */
    private function assertNoDependents(array $tables): void
    {
        $list = $this->inList($tables);
        $views = DB::select(
            "SELECT DISTINCT dependent.relname AS name
             FROM pg_depend d
             JOIN pg_rewrite r ON r.oid = d.objid
             JOIN pg_class dependent ON dependent.oid = r.ev_class
             JOIN pg_class source ON source.oid = d.refobjid
             JOIN pg_namespace n ON n.oid = source.relnamespace
             WHERE n.nspname = current_schema()
               AND source.relname IN ({$list})
               AND dependent.relkind IN ('v', 'm')"
        );
        if ($views !== []) {
            throw new RuntimeException('Column reorder cannot rebuild a table that a view depends on.');
        }

        $secured = DB::select(
            "SELECT c.relname AS name
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = current_schema()
               AND c.relrowsecurity
               AND c.relname IN ({$list})"
        );
        if ($secured !== []) {
            throw new RuntimeException('Column reorder cannot rebuild a table with row level security.');
        }

        $triggers = DB::select(
            "SELECT c.relname AS name
             FROM pg_trigger t
             JOIN pg_class c ON c.oid = t.tgrelid
             JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = current_schema()
               AND NOT t.tgisinternal
               AND c.relname IN ({$list})"
        );
        if ($triggers !== []) {
            throw new RuntimeException('Column reorder cannot rebuild a table that has a trigger.');
        }
    }

    /**
     * @param  list<string>  $tables
     * @return list<array{table: string, name: string, definition: string}>
     */
    private function postgresForeignKeys(array $tables): array
    {
        $list = $this->inList($tables);
        $rows = DB::select(
            "SELECT child.relname AS table_name, con.conname AS name, pg_get_constraintdef(con.oid) AS definition
             FROM pg_constraint con
             JOIN pg_class child ON child.oid = con.conrelid
             JOIN pg_class parent ON parent.oid = con.confrelid
             JOIN pg_namespace n ON n.oid = child.relnamespace
             WHERE con.contype = 'f'
               AND n.nspname = current_schema()
               AND (child.relname IN ({$list}) OR parent.relname IN ({$list}))
             ORDER BY child.relname, con.conname"
        );

        return array_map(fn ($row) => [
            'table' => $row->table_name,
            'name' => $row->name,
            'definition' => $row->definition,
        ], $rows);
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<array{name: string, definition: string}>>
     */
    private function postgresChecks(array $tables): array
    {
        return $this->postgresConstraintsByTable($tables, 'c');
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, array{name: string, definition: string}>
     */
    private function postgresPrimaryKeys(array $tables): array
    {
        $grouped = $this->postgresConstraintsByTable($tables, 'p');
        $primaryKeys = [];
        foreach ($tables as $table) {
            if (! isset($grouped[$table][0])) {
                throw new RuntimeException($table.' has no primary key to preserve.');
            }
            $primaryKeys[$table] = $grouped[$table][0];
        }

        return $primaryKeys;
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<array{name: string, definition: string}>>
     */
    private function postgresConstraintsByTable(array $tables, string $type): array
    {
        $rows = DB::select(
            "SELECT rel.relname AS table_name, con.conname AS name, pg_get_constraintdef(con.oid) AS definition
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace n ON n.oid = rel.relnamespace
             WHERE con.contype = ?
               AND n.nspname = current_schema()
               AND rel.relname IN (".$this->inList($tables).')
             ORDER BY rel.relname, con.conname',
            [$type]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->table_name][] = [
                'name' => $row->name,
                'definition' => $row->definition,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<array{name: string, definition: string}>>
     */
    private function postgresSecondaryIndexes(array $tables): array
    {
        $rows = DB::select(
            "SELECT t.relname AS table_name, ic.relname AS name, pg_get_indexdef(i.indexrelid) AS definition
             FROM pg_index i
             JOIN pg_class t ON t.oid = i.indrelid
             JOIN pg_class ic ON ic.oid = i.indexrelid
             JOIN pg_namespace n ON n.oid = t.relnamespace
             WHERE n.nspname = current_schema()
               AND i.indisprimary = false
               AND t.relname IN (".$this->inList($tables).')
             ORDER BY t.relname, ic.relname'
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->table_name][] = [
                'name' => $row->name,
                'definition' => $row->definition,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $tables
     * @return array{rows: int, foreign_keys: int, checks: int, indexes: int, primary_keys: int}
     */
    private function postgresSafetyCounts(array $tables): array
    {
        $list = $this->inList($tables);
        $rows = 0;
        foreach ($tables as $table) {
            $rows += (int) DB::table($table)->count();
        }

        $foreignKeys = (int) DB::selectOne(
            "SELECT COUNT(*) AS total
             FROM pg_constraint con
             JOIN pg_class child ON child.oid = con.conrelid
             JOIN pg_class parent ON parent.oid = con.confrelid
             JOIN pg_namespace n ON n.oid = child.relnamespace
             WHERE con.contype = 'f' AND n.nspname = current_schema()
               AND (child.relname IN ({$list}) OR parent.relname IN ({$list}))"
        )->total;

        $checks = (int) DB::selectOne(
            "SELECT COUNT(*) AS total
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace n ON n.oid = rel.relnamespace
             WHERE con.contype = 'c' AND n.nspname = current_schema() AND rel.relname IN ({$list})"
        )->total;

        $indexes = (int) DB::selectOne(
            "SELECT COUNT(*) AS total
             FROM pg_index i
             JOIN pg_class t ON t.oid = i.indrelid
             JOIN pg_namespace n ON n.oid = t.relnamespace
             WHERE n.nspname = current_schema() AND i.indisprimary = false AND t.relname IN ({$list})"
        )->total;

        $primaryKeys = (int) DB::selectOne(
            "SELECT COUNT(*) AS total
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace n ON n.oid = rel.relnamespace
             WHERE con.contype = 'p' AND n.nspname = current_schema() AND rel.relname IN ({$list})"
        )->total;

        return [
            'rows' => $rows,
            'foreign_keys' => $foreignKeys,
            'checks' => $checks,
            'indexes' => $indexes,
            'primary_keys' => $primaryKeys,
        ];
    }

    private function postgresSequence(string $table): ?string
    {
        if (! in_array('id', $this->orders[$table], true)) {
            return null;
        }

        $sequence = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS name", [$table]);
        if ($sequence !== null && $sequence->name !== null && $sequence->name !== '') {
            return $sequence->name;
        }

        $owned = DB::selectOne(
            "SELECT ns.nspname || '.' || seq.relname AS name
             FROM pg_class seq
             JOIN pg_depend dep ON dep.objid = seq.oid AND dep.deptype = 'a'
             JOIN pg_class tbl ON tbl.oid = dep.refobjid
             JOIN pg_attribute att ON att.attrelid = tbl.oid AND att.attnum = dep.refobjsubid
             JOIN pg_namespace ns ON ns.oid = seq.relnamespace
             WHERE seq.relkind = 'S' AND tbl.relname = ? AND att.attname = 'id'",
            [$table]
        );

        return $owned !== null && $owned->name !== null && $owned->name !== '' ? $owned->name : null;
    }

    /**
     * @param  list<string>  $actual
     * @param  list<string>  $desired
     */
    private function assertColumnSet(string $table, array $actual, array $desired): void
    {
        $missing = array_values(array_diff($desired, $actual));
        $extra = array_values(array_diff($actual, $desired));
        if ($missing === [] && $extra === []) {
            return;
        }

        throw new RuntimeException(
            $table.' columns do not match the reorder list. Missing: '
            .($missing === [] ? 'none' : implode(', ', $missing))
            .'. Extra: '.($extra === [] ? 'none' : implode(', ', $extra)).'.'
        );
    }

    private function assertTable(string $table): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            throw new RuntimeException('Missing table '.$table.'. Run the earlier migrations first.');
        }
    }

    private function isTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't';
    }

    /**
     * @param  list<string>  $values
     */
    private function inList(array $values): string
    {
        return implode(', ', array_map(fn (string $value) => $this->quoteLiteral($value), $values));
    }

    private function quoteLiteral(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function quoteQualified(string $name): string
    {
        return implode('.', array_map(fn (string $part) => $this->quote($part), explode('.', $name)));
    }
};
