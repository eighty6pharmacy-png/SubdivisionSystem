<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces application UUID keys with unique auto-increment integers.
 * Existing rows keep their relationships through an old-to-new id map.
 * Original UUID values are dropped, so this migration cannot be rolled back.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Tables whose own id becomes 1..n.
     *
     * @var list<string>
     */
    private array $surrogateTables = [
        'users',
        'lots',
        'buyers',
        'reservations',
        'downpayments',
        'downpayment_histories',
        'financing_records',
        'financing_payment_histories',
        'utility_bills',
        'payments',
        'incidents',
        'visitors',
        'announcements',
        'appointments',
    ];

    /**
     * Foreign key column => parent surrogate table. These copy the parent's new id.
     *
     * @var array<string, array<string, string>>
     */
    private array $foreignKeys = [
        'sessions' => ['user_id' => 'users'],
        'buyers' => ['user_id' => 'users', 'lot_id' => 'lots'],
        'admins' => ['user_id' => 'users'],
        'finance_officers' => ['user_id' => 'users'],
        'guards' => ['user_id' => 'users'],
        'residents' => ['user_id' => 'users'],
        'resident_lots' => ['resident_id' => 'users', 'lot_id' => 'lots'],
        'announcement_recipients' => ['announcement_id' => 'announcements', 'resident_id' => 'users'],
        'reservations' => ['user_id' => 'users', 'lot_id' => 'lots', 'buyer_id' => 'buyers'],
        'downpayments' => ['reservation_id' => 'reservations'],
        'downpayment_histories' => ['downpayment_id' => 'downpayments'],
        'financing_records' => ['buyer_id' => 'buyers', 'lot_id' => 'lots'],
        'financing_payment_histories' => ['financing_record_id' => 'financing_records'],
        'utility_bills' => ['resident_id' => 'users', 'lot_id' => 'lots'],
        'payments' => ['utility_bill_id' => 'utility_bills'],
        'incidents' => ['resident_id' => 'users'],
        'visitors' => ['resident_id' => 'users', 'guard_id' => 'users'],
        'announcements' => ['admin_id' => 'users'],
    ];

    /**
     * Partial and full unique indexes that must survive the table rebuild.
     *
     * @var array<string, string>
     */
    private array $canonicalIndexes = [
        'users_email_active_unique' => 'users (email) WHERE deleted_at IS NULL',
        'users_external_id_active_unique' => 'users (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL',
        'reservations_active_lot_unique' => "reservations (lot_id) WHERE status IN ('Pending', 'Reserved', 'Converted') AND deleted_at IS NULL",
        'downpayments_reservation_id_unique' => 'downpayments (reservation_id) WHERE deleted_at IS NULL',
        'utility_bills_lot_type_period_unique' => 'utility_bills (lot_id, type, billing_period) WHERE deleted_at IS NULL',
        'utility_bills_paymongo_checkout_id_unique' => 'utility_bills (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
        'downpayments_paymongo_checkout_id_unique' => 'downpayments (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
        'financing_records_paymongo_checkout_id_unique' => 'financing_records (paymongo_checkout_id) WHERE paymongo_checkout_id IS NOT NULL',
        'financing_records_buyer_id_unique' => 'financing_records (buyer_id)',
        'payments_paymongo_payment_id_unique' => 'payments (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
        'downpayment_histories_paymongo_payment_id_unique' => 'downpayment_histories (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
        'downpayment_histories_trn_unique' => 'downpayment_histories (trn)',
        'financing_payment_histories_paymongo_payment_id_unique' => 'financing_payment_histories (paymongo_payment_id) WHERE paymongo_payment_id IS NOT NULL',
        'financing_payment_histories_trn_unique' => 'financing_payment_histories (trn) WHERE trn IS NOT NULL',
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private array $maps = [];

    public function up(): void
    {
        if ($this->alreadyConverted()) {
            return;
        }

        $this->maps = $this->buildMaps();
        $this->assertMaps();
        $this->assertForeignKeys();
        $this->assertMorphs();

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->convertSqlite();

            return;
        }

        if ($driver === 'pgsql') {
            DB::transaction(function () {
                $this->convertRelational('pgsql');
            });

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->convertRelational($driver);

            return;
        }

        throw new RuntimeException('Unsupported database driver for integer key conversion: '.$driver);
    }

    public function down(): void
    {
        throw new RuntimeException('UUID keys were replaced with integers. Restore from a backup to reverse this migration.');
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function buildMaps(): array
    {
        $maps = [];

        foreach ($this->surrogateTables as $table) {
            $this->assertTable($table);
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'created_at')) {
                $query->orderBy('created_at');
            }
            $rows = $query->orderBy('id')->pluck('id');
            $map = [];
            $next = 1;
            foreach ($rows as $oldId) {
                $key = (string) $oldId;
                if (isset($map[$key])) {
                    throw new RuntimeException("Duplicate UUID {$key} on {$table}.id.");
                }
                $map[$key] = $next;
                $next++;
            }
            $maps[$table] = $map;
        }

        return $maps;
    }

    private function assertMaps(): void
    {
        foreach ($this->surrogateTables as $table) {
            $map = $this->maps[$table];
            $values = array_values($map);
            if (count($values) !== count(array_unique($values))) {
                throw new RuntimeException("Duplicate integer ids were generated for {$table}.");
            }
            if ($values !== [] && $values !== range(1, count($values))) {
                throw new RuntimeException("{$table} ids are not the contiguous sequence 1..n.");
            }
            if (count($map) !== DB::table($table)->count()) {
                throw new RuntimeException("{$table} id map does not cover every row.");
            }
        }
    }

    private function assertForeignKeys(): void
    {
        foreach ($this->foreignKeys as $table => $columns) {
            $this->assertTable($table);
            $nullable = $this->nullableColumns($table);
            foreach ($columns as $column => $parent) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Missing column {$table}.{$column}.");
                }
                foreach (DB::table($table)->select($column)->get() as $row) {
                    $this->assertMappedValue($parent, $row->{$column}, $table, $column, ! empty($nullable[$column]));
                }
            }
        }
    }

    private function assertMorphs(): void
    {
        foreach ($this->morphTables() as $table => $typeColumn) {
            $this->assertTable($table);
            $idColumn = $table === 'notifications' ? 'notifiable_id' : $this->morphKey();
            if (! Schema::hasColumn($table, $idColumn) || ! Schema::hasColumn($table, $typeColumn)) {
                throw new RuntimeException("Missing morph columns on {$table}.");
            }
            foreach (DB::table($table)->select([$idColumn, $typeColumn])->get() as $row) {
                $value = $row->{$idColumn};
                if ($value === null || $value === '') {
                    continue;
                }
                if ($row->{$typeColumn} !== User::class) {
                    throw new RuntimeException("{$table}.{$typeColumn} is {$row->{$typeColumn}}. Only user ids can become integers.");
                }
                $this->assertMappedValue('users', $value, $table, $idColumn, false);
            }
        }
    }

    private function assertMappedValue(string $parent, mixed $value, string $table, string $column, bool $nullable): void
    {
        if ($value === null || $value === '') {
            if (! $nullable) {
                throw new RuntimeException("{$table}.{$column} is required but empty.");
            }

            return;
        }

        if (! isset($this->maps[$parent][(string) $value])) {
            throw new RuntimeException("{$table}.{$column} value {$value} does not match a {$parent} row.");
        }
    }

    private function convertSqlite(): void
    {
        DB::connection()->getPdo()->exec('PRAGMA foreign_keys = OFF');

        try {
            DB::beginTransaction();
            foreach ($this->tablesToConvert() as $table) {
                $this->rebuildSqliteTable($table);
            }
            $this->restoreCanonicalIndexes();
            $this->syncSqliteSequences();
            $this->assertSqliteIntegrity();
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        } finally {
            DB::connection()->getPdo()->exec('PRAGMA foreign_keys = ON');
        }
    }

    private function rebuildSqliteTable(string $table): void
    {
        $columns = DB::select('PRAGMA table_info('.$this->quote($table).')');
        if ($columns === []) {
            throw new RuntimeException("Could not read columns for {$table}.");
        }

        $primaryKey = [];
        foreach ($columns as $column) {
            if ((int) $column->pk > 0) {
                $primaryKey[(int) $column->pk] = $column->name;
            }
        }
        ksort($primaryKey);
        $primaryKey = array_values($primaryKey);
        if ($primaryKey === []) {
            throw new RuntimeException("{$table} has no primary key.");
        }

        $indexes = DB::select(
            "SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql IS NOT NULL",
            [$table]
        );
        $foreignKeys = $this->sqliteForeignKeys($table);
        $autoIncrement = count($primaryKey) === 1
            && $primaryKey[0] === 'id'
            && in_array($table, $this->surrogateTables, true);

        $temporary = $table.'__int_new';
        Schema::dropIfExists($temporary);

        $definitions = [];
        foreach ($columns as $column) {
            $definitions[] = $this->sqliteColumnDefinition($table, $column, $primaryKey, $autoIncrement);
        }
        if (count($primaryKey) > 1) {
            $definitions[] = 'PRIMARY KEY ('.implode(', ', array_map(fn (string $name) => $this->quote($name), $primaryKey)).')';
        }
        foreach ($foreignKeys as $foreignKey) {
            $definitions[] = $foreignKey;
        }

        DB::statement('CREATE TABLE '.$this->quote($temporary).' ('.implode(', ', $definitions).')');

        foreach (DB::table($table)->get() as $row) {
            DB::table($temporary)->insert($this->remapRow($table, (array) $row));
        }

        Schema::drop($table);
        DB::statement('ALTER TABLE '.$this->quote($temporary).' RENAME TO '.$this->quote($table));

        foreach ($indexes as $index) {
            if (isset($this->canonicalIndexes[$index->name])) {
                continue;
            }
            DB::statement($index->sql);
        }
    }

    private function sqliteColumnDefinition(string $table, object $column, array $primaryKey, bool $autoIncrement): string
    {
        $name = $this->quote($column->name);
        $converted = $this->isConverted($table, $column->name);
        $type = $converted ? 'INTEGER' : (($column->type ?? '') !== '' ? $column->type : 'TEXT');
        $singlePrimary = count($primaryKey) === 1 && $primaryKey[0] === $column->name;

        if ($singlePrimary && $autoIncrement) {
            return $name.' INTEGER PRIMARY KEY AUTOINCREMENT';
        }

        $sql = $name.' '.$type;
        if ($singlePrimary) {
            $sql .= ' PRIMARY KEY';
        }
        if ((int) $column->notnull === 1) {
            $sql .= ' NOT NULL';
        }
        if (! $converted && $column->dflt_value !== null) {
            $sql .= ' DEFAULT '.$column->dflt_value;
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
            $groups[$foreignKey->id]['on_update'] = $this->sqliteAction($foreignKey->on_update);
            $groups[$foreignKey->id]['on_delete'] = $this->sqliteAction($foreignKey->on_delete);
            $groups[$foreignKey->id]['from'][(int) $foreignKey->seq] = $foreignKey->from;
            $groups[$foreignKey->id]['to'][(int) $foreignKey->seq] = $foreignKey->to;
        }

        $clauses = [];
        foreach ($groups as $group) {
            ksort($group['from']);
            ksort($group['to']);
            $clauses[] = 'FOREIGN KEY ('.implode(', ', array_map(fn (string $name) => $this->quote($name), $group['from'])).')'
                .' REFERENCES '.$this->quote($group['table'])
                .' ('.implode(', ', array_map(fn (string $name) => $this->quote($name), $group['to'])).')'
                .' ON UPDATE '.$group['on_update']
                .' ON DELETE '.$group['on_delete'];
        }

        return $clauses;
    }

    private function sqliteAction(string $action): string
    {
        $action = strtoupper($action);
        $allowed = ['NO ACTION', 'RESTRICT', 'CASCADE', 'SET NULL', 'SET DEFAULT'];
        if (! in_array($action, $allowed, true)) {
            throw new RuntimeException('Unsupported SQLite foreign key action: '.$action);
        }

        return $action;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function remapRow(string $table, array $row): array
    {
        foreach ($this->lookupColumns($table) as $column => $parent) {
            if (! array_key_exists($column, $row)) {
                throw new RuntimeException("Missing {$table}.{$column} while copying rows.");
            }
            $row[$column] = $this->mappedValue($parent, $row[$column], $table, $column);
        }

        if (in_array($table, $this->surrogateTables, true)) {
            $oldId = (string) $row['id'];
            if (! isset($this->maps[$table][$oldId])) {
                throw new RuntimeException("No integer id was assigned for {$table} {$oldId}.");
            }
            $row['id'] = $this->maps[$table][$oldId];
        }

        return $row;
    }

    private function mappedValue(string $parent, mixed $value, string $table, string $column): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = (string) $value;
        if (! isset($this->maps[$parent][$key])) {
            throw new RuntimeException("{$table}.{$column} value {$value} does not match a {$parent} row.");
        }

        return $this->maps[$parent][$key];
    }

    private function syncSqliteSequences(): void
    {
        $exists = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'");
        if ($exists === []) {
            return;
        }

        foreach ($this->surrogateTables as $table) {
            $max = DB::table($table)->max('id');
            if ($max === null) {
                DB::delete('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
                continue;
            }
            DB::statement(
                'INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?) ON CONFLICT(name) DO UPDATE SET seq = excluded.seq',
                [$table, (int) $max]
            );
        }
    }

    private function assertSqliteIntegrity(): void
    {
        $violations = DB::select('PRAGMA foreign_key_check');
        if ($violations !== []) {
            throw new RuntimeException('Foreign key check failed after integer conversion.');
        }

        foreach ($this->surrogateTables as $table) {
            $count = DB::table($table)->count();
            $distinct = (int) DB::table($table)->distinct()->count('id');
            if ($count !== $distinct) {
                throw new RuntimeException("{$table}.id contains duplicate integers.");
            }
            if ($count === 0) {
                continue;
            }
            $max = (int) DB::table($table)->max('id');
            $min = (int) DB::table($table)->min('id');
            if ($min !== 1 || $max !== $count) {
                throw new RuntimeException("{$table} integer ids are not the contiguous sequence 1..{$count}.");
            }
        }
    }

    private function convertRelational(string $driver): void
    {
        $indexes = $this->snapshotIndexes($driver);
        $foreignKeys = $this->snapshotForeignKeys($driver);
        $required = [];
        foreach ($this->tablesToConvert() as $table) {
            $required[$table] = $this->nullableColumns($table);
        }

        foreach ($this->tablesToConvert() as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (array_keys($this->lookupColumns($table) + $this->surrogateIdColumn($table)) as $column) {
                    $blueprint->unsignedBigInteger($column.'__int')->nullable();
                }
            });
        }

        $this->fillShadowColumns();
        $this->assertShadowsFilled();
        $this->dropForeignKeys($driver, $foreignKeys);
        $this->dropSecondaryIndexes($driver, $indexes);

        foreach ($this->tablesToConvert() as $table) {
            $this->swapShadowColumns($driver, $table, $required[$table]);
        }

        $this->bindSequences($driver);
        $this->restoreIndexes($driver, $indexes);
        $this->restoreForeignKeys($driver, $foreignKeys);
        if (in_array($driver, ['pgsql'], true)) {
            $this->restoreCanonicalIndexes();
        }
        $this->assertConvertedCounts();
    }

    private function fillShadowColumns(): void
    {
        foreach ($this->tablesToConvert() as $table) {
            if (in_array($table, $this->surrogateTables, true)) {
                foreach ($this->maps[$table] as $oldId => $newId) {
                    DB::table($table)->where('id', $oldId)->update(['id__int' => $newId]);
                }
            }

            foreach ($this->lookupColumns($table) as $column => $parent) {
                foreach ($this->maps[$parent] as $oldId => $newId) {
                    $query = DB::table($table)->where($column, $oldId);
                    if ($table === 'notifications') {
                        $query->where('notifiable_type', User::class);
                    } elseif (isset($this->permissionMorphTables()[$table])) {
                        $query->where('model_type', User::class);
                    }
                    $query->update([$column.'__int' => $newId]);
                }
            }
        }
    }

    private function assertShadowsFilled(): void
    {
        foreach ($this->tablesToConvert() as $table) {
            $columns = array_keys($this->lookupColumns($table) + $this->surrogateIdColumn($table));
            foreach ($columns as $column) {
                $missing = DB::table($table)->whereNotNull($column)->whereNull($column.'__int')->count();
                if ($missing > 0) {
                    throw new RuntimeException("{$missing} {$table}.{$column} values were not mapped to an integer.");
                }
            }
        }
    }

    /**
     * @param  list<array{name: string, table: string, definition: string}>  $foreignKeys
     */
    private function dropForeignKeys(string $driver, array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $keyword = $driver === 'pgsql' ? 'CONSTRAINT' : 'FOREIGN KEY';
            DB::statement(
                'ALTER TABLE '.$this->quote($foreignKey['table'], $driver)
                .' DROP '.$keyword.' '.$this->quote($foreignKey['name'], $driver)
            );
        }
    }

    /**
     * @param  list<array{name: string, table: string, definition: string, unique: bool, columns: list<string>}>  $indexes
     */
    private function dropSecondaryIndexes(string $driver, array $indexes): void
    {
        foreach ($indexes as $index) {
            if ($driver === 'pgsql') {
                $constraint = DB::selectOne('SELECT 1 AS present FROM pg_constraint WHERE conname = ?', [$index['name']]);
                if ($constraint !== null) {
                    DB::statement(
                        'ALTER TABLE '.$this->quote($index['table'], $driver)
                        .' DROP CONSTRAINT '.$this->quote($index['name'], $driver)
                    );
                } else {
                    DB::statement('DROP INDEX IF EXISTS '.$this->quote($index['name'], $driver));
                }
                continue;
            }

            DB::statement(
                'ALTER TABLE '.$this->quote($index['table'], $driver).' DROP INDEX '.$this->quote($index['name'], $driver)
            );
        }
    }

    /**
     * @param  array<string, bool>  $nullable
     */
    private function swapShadowColumns(string $driver, string $table, array $nullable): void
    {
        $columns = array_keys($this->lookupColumns($table) + $this->surrogateIdColumn($table));
        $primaryKey = $this->primaryKeyColumns($driver, $table);
        $replacePrimary = array_intersect($primaryKey, $columns) !== [];

        if ($replacePrimary) {
            if ($driver === 'pgsql') {
                $name = $this->primaryKeyName($table);
                DB::statement('ALTER TABLE '.$this->quote($table, $driver).' DROP CONSTRAINT '.$this->quote($name, $driver));
            } else {
                DB::statement('ALTER TABLE '.$this->quote($table, $driver).' DROP PRIMARY KEY');
            }
        }

        foreach ($columns as $column) {
            DB::statement('ALTER TABLE '.$this->quote($table, $driver).' DROP COLUMN '.$this->quote($column, $driver));
        }

        foreach ($columns as $column) {
            $quotedTable = $this->quote($table, $driver);
            $from = $this->quote($column.'__int', $driver);
            $to = $this->quote($column, $driver);
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$quotedTable} RENAME COLUMN {$from} TO {$to}");
                if (empty($nullable[$column])) {
                    DB::statement("ALTER TABLE {$quotedTable} ALTER COLUMN {$to} SET NOT NULL");
                }
            } else {
                $nullSql = empty($nullable[$column]) ? 'NOT NULL' : 'NULL';
                DB::statement("ALTER TABLE {$quotedTable} CHANGE {$from} {$to} BIGINT UNSIGNED {$nullSql}");
            }
        }

        if ($replacePrimary) {
            $quoted = implode(', ', array_map(fn (string $column) => $this->quote($column, $driver), $primaryKey));
            DB::statement('ALTER TABLE '.$this->quote($table, $driver).' ADD PRIMARY KEY ('.$quoted.')');
        }

        if ($driver !== 'pgsql' && in_array($table, $this->surrogateTables, true)) {
            DB::statement(
                'ALTER TABLE '.$this->quote($table, $driver).' MODIFY '.$this->quote('id', $driver).' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
    }

    private function bindSequences(string $driver): void
    {
        if ($driver !== 'pgsql') {
            return;
        }

        foreach ($this->surrogateTables as $table) {
            $sequence = $table.'_id_seq';
            DB::statement('CREATE SEQUENCE IF NOT EXISTS '.$this->quote($sequence, $driver));
            DB::statement(
                'ALTER TABLE '.$this->quote($table, $driver)
                .' ALTER COLUMN '.$this->quote('id', $driver)
                .' SET DEFAULT nextval('.$this->quoteLiteral($sequence).')'
            );
            DB::statement('ALTER SEQUENCE '.$this->quote($sequence, $driver).' OWNED BY '.$this->quote($table, $driver).'.'.$this->quote('id', $driver));

            $max = DB::table($table)->max('id');
            if ($max === null) {
                DB::statement('SELECT setval('.$this->quoteLiteral($sequence).', 1, false)');
            } else {
                DB::statement('SELECT setval('.$this->quoteLiteral($sequence).', '.((int) $max).', true)');
            }
        }
    }

    /**
     * @param  list<array{name: string, table: string, definition: string, unique: bool, columns: list<string>}>  $indexes
     */
    private function restoreIndexes(string $driver, array $indexes): void
    {
        foreach ($indexes as $index) {
            if (isset($this->canonicalIndexes[$index['name']]) && $driver === 'pgsql') {
                continue;
            }
            if ($driver === 'pgsql') {
                DB::statement($index['definition']);
                continue;
            }

            $unique = $index['unique'] ? 'UNIQUE ' : '';
            $columns = implode(', ', array_map(fn (string $column) => $this->quote($column, $driver), $index['columns']));
            DB::statement(
                'CREATE '.$unique.'INDEX '.$this->quote($index['name'], $driver)
                .' ON '.$this->quote($index['table'], $driver).' ('.$columns.')'
            );
        }
    }

    /**
     * @param  list<array{name: string, table: string, definition: string}>  $foreignKeys
     */
    private function restoreForeignKeys(string $driver, array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            if ($driver === 'pgsql') {
                DB::statement(
                    'ALTER TABLE '.$this->quote($foreignKey['table'], $driver)
                    .' ADD CONSTRAINT '.$this->quote($foreignKey['name'], $driver).' '.$foreignKey['definition']
                );
                continue;
            }

            DB::statement(
                'ALTER TABLE '.$this->quote($foreignKey['table'], $driver)
                .' ADD CONSTRAINT '.$this->quote($foreignKey['name'], $driver).' '.$foreignKey['definition']
            );
        }
    }

    /**
     * @return list<array{name: string, table: string, definition: string, unique: bool, columns: list<string>}>
     */
    private function snapshotIndexes(string $driver): array
    {
        $indexes = [];
        $tables = $this->tablesToConvert();

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT t.relname AS table_name, ic.relname AS name, pg_get_indexdef(i.indexrelid) AS definition
                 FROM pg_index i
                 JOIN pg_class t ON t.oid = i.indrelid
                 JOIN pg_class ic ON ic.oid = i.indexrelid
                 JOIN pg_namespace n ON n.oid = t.relnamespace
                 WHERE n.nspname = current_schema() AND i.indisprimary = false AND t.relname IN ('.$this->inList($tables).')'
            );
            foreach ($rows as $row) {
                $indexes[] = [
                    'name' => $row->name,
                    'table' => $row->table_name,
                    'definition' => $row->definition,
                    'unique' => false,
                    'columns' => [],
                ];
            }

            return $indexes;
        }

        $rows = DB::select(
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME <> \'PRIMARY\' AND TABLE_NAME IN ('.$this->inList($tables).')
             ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX'
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->TABLE_NAME.'|'.$row->INDEX_NAME]['name'] = $row->INDEX_NAME;
            $grouped[$row->TABLE_NAME.'|'.$row->INDEX_NAME]['table'] = $row->TABLE_NAME;
            $grouped[$row->TABLE_NAME.'|'.$row->INDEX_NAME]['unique'] = (int) $row->NON_UNIQUE === 0;
            $grouped[$row->TABLE_NAME.'|'.$row->INDEX_NAME]['columns'][(int) $row->SEQ_IN_INDEX] = $row->COLUMN_NAME;
        }
        foreach ($grouped as $index) {
            ksort($index['columns']);
            $indexes[] = [
                'name' => $index['name'],
                'table' => $index['table'],
                'definition' => '',
                'unique' => $index['unique'],
                'columns' => array_values($index['columns']),
            ];
        }

        return $indexes;
    }

    /**
     * @return list<array{name: string, table: string, definition: string}>
     */
    private function snapshotForeignKeys(string $driver): array
    {
        $tables = $this->tablesToConvert();

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT rel.relname AS table_name, con.conname AS name, pg_get_constraintdef(con.oid) AS definition
                 FROM pg_constraint con
                 JOIN pg_class rel ON rel.oid = con.conrelid
                 JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                 WHERE con.contype = \'f\' AND nsp.nspname = current_schema() AND rel.relname IN ('.$this->inList($tables).')'
            );

            return array_map(fn ($row) => [
                'name' => $row->name,
                'table' => $row->table_name,
                'definition' => $row->definition,
            ], $rows);
        }

        $rows = DB::select(
            'SELECT k.CONSTRAINT_NAME, k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
                    k.ORDINAL_POSITION, r.DELETE_RULE, r.UPDATE_RULE
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
              AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
              AND r.TABLE_NAME = k.TABLE_NAME
             WHERE k.TABLE_SCHEMA = DATABASE()
               AND k.REFERENCED_TABLE_NAME IS NOT NULL
               AND k.TABLE_NAME IN ('.$this->inList($tables).')
             ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION'
        );

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->TABLE_NAME.'|'.$row->CONSTRAINT_NAME;
            $grouped[$key]['name'] = $row->CONSTRAINT_NAME;
            $grouped[$key]['table'] = $row->TABLE_NAME;
            $grouped[$key]['referenced'] = $row->REFERENCED_TABLE_NAME;
            $grouped[$key]['delete'] = $row->DELETE_RULE;
            $grouped[$key]['update'] = $row->UPDATE_RULE;
            $grouped[$key]['columns'][(int) $row->ORDINAL_POSITION] = $row->COLUMN_NAME;
            $grouped[$key]['referenced_columns'][(int) $row->ORDINAL_POSITION] = $row->REFERENCED_COLUMN_NAME;
        }

        $foreignKeys = [];
        foreach ($grouped as $foreignKey) {
            ksort($foreignKey['columns']);
            ksort($foreignKey['referenced_columns']);
            $columns = implode(', ', array_map(fn (string $column) => $this->quote($column, $driver), $foreignKey['columns']));
            $referenced = implode(', ', array_map(fn (string $column) => $this->quote($column, $driver), $foreignKey['referenced_columns']));
            $foreignKeys[] = [
                'name' => $foreignKey['name'],
                'table' => $foreignKey['table'],
                'definition' => 'FOREIGN KEY ('.$columns.') REFERENCES '.$this->quote($foreignKey['referenced'], $driver)
                    .' ('.$referenced.') ON UPDATE '.$foreignKey['update'].' ON DELETE '.$foreignKey['delete'],
            ];
        }

        return $foreignKeys;
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(string $driver, string $table): array
    {
        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT a.attname AS name
                 FROM pg_index i
                 JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                 WHERE i.indrelid = ?::regclass AND i.indisprimary
                 ORDER BY array_position(i.indkey, a.attnum)',
                [$table]
            );
        } else {
            $rows = DB::select(
                'SELECT COLUMN_NAME AS name FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = \'PRIMARY\'
                 ORDER BY SEQ_IN_INDEX',
                [$table]
            );
        }

        $columns = array_map(fn ($row) => $row->name, $rows);
        if ($columns === []) {
            throw new RuntimeException("{$table} has no primary key.");
        }

        return $columns;
    }

    private function primaryKeyName(string $table): string
    {
        $row = DB::selectOne(
            "SELECT con.conname AS name
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
             WHERE con.contype = 'p' AND rel.relname = ? AND nsp.nspname = current_schema()",
            [$table]
        );
        if ($row === null) {
            throw new RuntimeException("{$table} has no primary key.");
        }

        return $row->name;
    }

    private function assertConvertedCounts(): void
    {
        foreach ($this->surrogateTables as $table) {
            $count = DB::table($table)->count();
            $distinct = (int) DB::table($table)->distinct()->count('id');
            if ($count !== $distinct) {
                throw new RuntimeException("{$table}.id contains duplicate integers.");
            }
        }
    }

    private function restoreCanonicalIndexes(): void
    {
        foreach ($this->canonicalIndexes as $name => $definition) {
            DB::statement('DROP INDEX IF EXISTS '.$name);
            DB::statement('CREATE UNIQUE INDEX '.$name.' ON '.$definition);
        }
    }

    /**
     * @return array<string, string>
     */
    private function lookupColumns(string $table): array
    {
        $columns = $this->foreignKeys[$table] ?? [];
        if ($table === 'notifications') {
            $columns['notifiable_id'] = 'users';
        }
        if (isset($this->permissionMorphTables()[$table])) {
            $columns[$this->morphKey()] = 'users';
        }

        return $columns;
    }

    /**
     * @return array<string, string>
     */
    private function surrogateIdColumn(string $table): array
    {
        return in_array($table, $this->surrogateTables, true) ? ['id' => $table] : [];
    }

    private function isConverted(string $table, string $column): bool
    {
        return array_key_exists($column, $this->lookupColumns($table) + $this->surrogateIdColumn($table));
    }

    /**
     * @return list<string>
     */
    private function tablesToConvert(): array
    {
        return array_values(array_unique(array_merge(
            $this->surrogateTables,
            array_keys($this->foreignKeys),
            array_keys($this->morphTables()),
            ['notifications']
        )));
    }

    /**
     * @return array<string, string> table => type column
     */
    private function morphTables(): array
    {
        return $this->permissionMorphTables() + ['notifications' => 'notifiable_type'];
    }

    /**
     * @return array<string, string> table => type column
     */
    private function permissionMorphTables(): array
    {
        return [
            config('permission.table_names.model_has_roles') => 'model_type',
            config('permission.table_names.model_has_permissions') => 'model_type',
        ];
    }

    private function morphKey(): string
    {
        return config('permission.column_names.model_morph_key') ?: 'model_id';
    }

    private function alreadyConverted(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id')) {
            return false;
        }

        $type = strtolower($this->columnType('users', 'id'));

        return in_array($type, ['integer', 'int', 'bigint', 'int8', 'int4', 'bigserial', 'serial'], true);
    }

    private function columnType(string $table, string $column): string
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA table_info('.$this->quote($table).')') as $info) {
                if ($info->name === $column) {
                    return (string) $info->type;
                }
            }

            return '';
        }

        $schema = $driver === 'pgsql' ? 'current_schema()' : 'DATABASE()';
        $row = DB::selectOne(
            "SELECT DATA_TYPE AS type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = {$schema} AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return (string) ($row->type ?? '');
    }

    /**
     * @return array<string, bool>
     */
    private function nullableColumns(string $table): array
    {
        $driver = DB::getDriverName();
        $nullable = [];

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA table_info('.$this->quote($table).')') as $column) {
                $nullable[$column->name] = (int) $column->notnull !== 1;
            }

            return $nullable;
        }

        $schema = $driver === 'pgsql' ? 'current_schema()' : 'DATABASE()';
        $rows = DB::select(
            "SELECT COLUMN_NAME AS name, IS_NULLABLE AS nullable
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = {$schema} AND TABLE_NAME = ?",
            [$table]
        );
        foreach ($rows as $row) {
            $nullable[$row->name] = strtoupper((string) $row->nullable) === 'YES';
        }

        return $nullable;
    }

    private function assertTable(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException("Missing table {$table}. Run the earlier migrations first.");
        }
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

    private function quote(string $identifier, string $driver = 'sqlite'): string
    {
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return '`'.str_replace('`', '``', $identifier).'`';
        }

        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
