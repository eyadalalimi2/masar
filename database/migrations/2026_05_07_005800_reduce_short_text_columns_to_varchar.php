<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{table:string,column:string,length:int,indexName?:string,indexPrefix?:int}>
     */
    private array $targets = [
        ['table' => 'audit_logs', 'column' => 'user_agent', 'length' => 1024, 'indexName' => 'audit_logs_user_agent_idx', 'indexPrefix' => 191],
        ['table' => 'admin_audit_logs', 'column' => 'user_agent', 'length' => 1024, 'indexName' => 'admin_audit_logs_user_agent_idx', 'indexPrefix' => 191],
        ['table' => 'audit_trails', 'column' => 'user_agent', 'length' => 1024, 'indexName' => 'audit_trails_user_agent_idx', 'indexPrefix' => 191],

        ['table' => 'sessions', 'column' => 'user_agent', 'length' => 1024],

        ['table' => 'failed_jobs', 'column' => 'connection', 'length' => 255],
        ['table' => 'failed_jobs', 'column' => 'queue', 'length' => 255],

        ['table' => 'accounts', 'column' => 'fcm_token', 'length' => 512],

        ['table' => 'customers', 'column' => 'working_hours', 'length' => 1000],
        ['table' => 'branches', 'column' => 'working_hours', 'length' => 1000],
        ['table' => 'suppliers', 'column' => 'working_hours', 'length' => 1000],
    ];

    public function up(): void
    {
        foreach ($this->targets as $target) {
            $this->alterTextToVarcharIfSafe(
                $target['table'],
                $target['column'],
                $target['length'],
                $target['indexName'] ?? null,
                $target['indexPrefix'] ?? null
            );
        }
    }

    public function down(): void
    {
        foreach ($this->targets as $target) {
            if (isset($target['indexName'])) {
                $this->dropIndexIfExists($target['table'], $target['indexName']);
            }

            $this->alterVarcharToTextIfNeeded($target['table'], $target['column']);
        }
    }

    private function alterTextToVarcharIfSafe(
        string $table,
        string $column,
        int $length,
        ?string $indexName,
        ?int $indexPrefix
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $columnMeta = $this->getColumnMeta($table, $column);
        if ($columnMeta === null) {
            return;
        }

        $dataType = strtolower((string) ($columnMeta->data_type ?? ''));
        if (! in_array($dataType, ['tinytext', 'text', 'mediumtext', 'longtext'], true)) {
            return;
        }

        $maxLength = $this->getMaxCharLength($table, $column);
        if ($maxLength > $length) {
            return;
        }

        $nullSql = strtoupper((string) $columnMeta->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';
        $defaultSql = '';

        if ($columnMeta->column_default !== null) {
            $defaultSql = ' DEFAULT ' . DB::getPdo()->quote((string) $columnMeta->column_default);
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` VARCHAR(%d) %s%s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column),
            $length,
            $nullSql,
            $defaultSql
        ));

        if ($indexName !== null && $indexName !== '') {
            $this->addIndexIfMissing($table, $column, $indexName, $indexPrefix);
        }
    }

    private function alterVarcharToTextIfNeeded(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $columnMeta = $this->getColumnMeta($table, $column);
        if ($columnMeta === null) {
            return;
        }

        $dataType = strtolower((string) ($columnMeta->data_type ?? ''));
        if ($dataType !== 'varchar') {
            return;
        }

        $nullSql = strtoupper((string) $columnMeta->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` TEXT %s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column),
            $nullSql
        ));
    }

    private function getMaxCharLength(string $table, string $column): int
    {
        $safeTable = str_replace('`', '``', $table);
        $safeColumn = str_replace('`', '``', $column);

        $value = DB::selectOne(sprintf(
            'SELECT COALESCE(MAX(CHAR_LENGTH(`%s`)), 0) AS max_len FROM `%s`',
            $safeColumn,
            $safeTable
        ));

        return (int) ($value->max_len ?? 0);
    }

    private function getColumnMeta(string $table, string $column): ?object
    {
        $dbName = DB::getDatabaseName();
        if (! is_string($dbName) || trim($dbName) === '') {
            return null;
        }

        return DB::table('information_schema.columns')
            ->select(['data_type', 'is_nullable', 'column_default'])
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->first();
    }

    private function addIndexIfMissing(string $table, string $column, string $indexName, ?int $indexPrefix): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $columnSql = '`' . str_replace('`', '``', $column) . '`';
        if ($indexPrefix !== null && $indexPrefix > 0) {
            $columnSql .= '(' . $indexPrefix . ')';
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            str_replace('`', '``', $table),
            str_replace('`', '``', $indexName),
            $columnSql
        ));
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            str_replace('`', '``', $table),
            str_replace('`', '``', $indexName)
        ));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();
        if (! is_string($dbName) || trim($dbName) === '') {
            return false;
        }

        $value = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->value('index_name');

        return is_string($value) && $value !== '';
    }
};
