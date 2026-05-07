<?php

namespace App\Services\Data;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UniqueConstraintMaintenanceService
{
    /**
     * @var array<int, array{table:string,column:string,index:string,type:string}>
     */
    private array $targets = [
        ['table' => 'suppliers', 'column' => 'phone', 'index' => 'suppliers_phone_unique', 'type' => 'phone'],
        ['table' => 'suppliers', 'column' => 'email', 'index' => 'suppliers_email_unique', 'type' => 'email'],
        ['table' => 'branches', 'column' => 'phone', 'index' => 'branches_phone_unique', 'type' => 'phone'],
        ['table' => 'distributors', 'column' => 'phone', 'index' => 'distributors_phone_unique', 'type' => 'phone'],
        ['table' => 'products', 'column' => 'sku', 'index' => 'products_sku_unique', 'type' => 'sku'],
        ['table' => 'products', 'column' => 'barcode', 'index' => 'products_barcode_unique', 'type' => 'barcode'],
    ];

    /**
     * @return array<string, array<string, int|string>>
     */
    public function cleanupDuplicates(bool $dryRun = false): array
    {
        $summary = [];

        foreach ($this->targetsWithUuid() as $target) {
            $table = $target['table'];
            $column = $target['column'];
            $type = $target['type'];

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $maxLength = $this->columnMaxLength($table, $column);
            $duplicateKeys = $this->duplicateKeys($table, $column, $type);

            $updated = 0;
            foreach ($duplicateKeys as $key) {
                $rows = $this->rowsForDuplicateKey($table, $column, $type, $key);

                foreach ($rows as $index => $row) {
                    if ($index === 0) {
                        continue;
                    }

                    $rowId = (int) $row->id;
                    $newValue = $this->resolveReplacementValue(
                        $table,
                        $column,
                        $type,
                        is_string($row->{$column}) ? (string) $row->{$column} : null,
                        $rowId,
                        $maxLength
                    );

                    if (! $dryRun) {
                        DB::table($table)->where('id', $rowId)->update([$column => $newValue]);
                    }

                    $updated++;
                }
            }

            $summary[$table . '.' . $column] = [
                'duplicates_fixed' => $updated,
                'duplicate_groups' => count($duplicateKeys),
                'mode' => $dryRun ? 'dry-run' : 'apply',
            ];
        }

        return $summary;
    }

    /**
     * @return array<string, string>
     */
    public function ensureUniqueConstraints(): array
    {
        $results = [];

        foreach ($this->targetsWithUuid() as $target) {
            $table = $target['table'];
            $column = $target['column'];
            $indexName = $target['index'];

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if ($this->hasUniqueIndexForColumn($table, $column)) {
                $results[$table . '.' . $column] = 'already_unique';
                continue;
            }

            $indexMeta = $this->getIndexMetaByName($table, $indexName);
            if ($indexMeta !== null) {
                $this->dropIndexByName($table, $indexName);
            }

            $quotedTable = $this->quoteIdentifier($table);
            $quotedIndex = $this->quoteIdentifier($indexName);
            $quotedColumn = $this->quoteIdentifier($column);

            DB::statement("ALTER TABLE {$quotedTable} ADD UNIQUE {$quotedIndex} ({$quotedColumn})");
            $results[$table . '.' . $column] = 'unique_added';
        }

        return $results;
    }

    /**
     * @return array<int, array{table:string,column:string,index:string,type:string}>
     */
    private function targetsWithUuid(): array
    {
        $targets = $this->targets;

        $uuidTables = DB::table('information_schema.columns')
            ->select('table_name')
            ->where('table_schema', DB::getDatabaseName())
            ->where('column_name', 'uuid')
            ->pluck('table_name')
            ->filter(fn($table) => is_string($table) && trim($table) !== '')
            ->map(fn($table) => trim((string) $table))
            ->unique()
            ->values();

        foreach ($uuidTables as $table) {
            $targets[] = [
                'table' => $table,
                'column' => 'uuid',
                'index' => $table . '_uuid_unique',
                'type' => 'uuid',
            ];
        }

        return collect($targets)
            ->unique(fn(array $target) => $target['table'] . '.' . $target['column'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function duplicateKeys(string $table, string $column, string $type): array
    {
        $expression = $this->normalizedExpression($column, $type);

        return DB::table($table)
            ->selectRaw($expression . ' as dedupe_key')
            ->whereNotNull($column)
            ->whereRaw('TRIM(' . $this->quoteIdentifier($column) . ') <> ""')
            ->groupBy('dedupe_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('dedupe_key')
            ->filter(fn($key) => is_string($key) && trim($key) !== '')
            ->map(fn($key) => (string) $key)
            ->values()
            ->all();
    }

    /**
     * @return array<int, object>
     */
    private function rowsForDuplicateKey(string $table, string $column, string $type, string $key): array
    {
        $quotedColumn = $this->quoteIdentifier($column);

        $query = DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id');

        if ($type === 'email') {
            $query->whereRaw('LOWER(TRIM(' . $quotedColumn . ')) = ?', [$key]);
        } else {
            $query->whereRaw('TRIM(' . $quotedColumn . ') = ?', [$key]);
        }

        return $query->get()->all();
    }

    private function resolveReplacementValue(
        string $table,
        string $column,
        string $type,
        ?string $originalValue,
        int $rowId,
        ?int $maxLength
    ): ?string {
        if ($type === 'email') {
            return null;
        }

        if ($type === 'uuid') {
            return (string) Str::uuid();
        }

        $base = trim((string) ($originalValue ?? ''));
        if ($base === '') {
            $base = $column;
        }

        $suffix = '-dup-' . $rowId;
        $head = $base;

        if (is_int($maxLength) && $maxLength > 0) {
            $headLimit = max(1, $maxLength - strlen($suffix));
            $head = substr($head, 0, $headLimit);
        }

        $candidate = $head . $suffix;
        if (is_int($maxLength) && $maxLength > 0 && strlen($candidate) > $maxLength) {
            $candidate = substr($candidate, 0, $maxLength);
        }

        $attempt = 1;
        while ($this->valueExists($table, $column, $candidate, $rowId)) {
            $extra = '-' . $attempt;
            $head2 = $head;
            if (is_int($maxLength) && $maxLength > 0) {
                $headLimit = max(1, $maxLength - strlen($suffix . $extra));
                $head2 = substr($head, 0, $headLimit);
            }

            $candidate = $head2 . $suffix . $extra;
            if (is_int($maxLength) && $maxLength > 0 && strlen($candidate) > $maxLength) {
                $candidate = substr($candidate, 0, $maxLength);
            }

            $attempt++;
        }

        return $candidate;
    }

    private function valueExists(string $table, string $column, ?string $value, int $excludeId): bool
    {
        if ($value === null) {
            return false;
        }

        return DB::table($table)
            ->where($column, $value)
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    private function normalizedExpression(string $column, string $type): string
    {
        $quotedColumn = $this->quoteIdentifier($column);

        if ($type === 'email') {
            return 'LOWER(TRIM(' . $quotedColumn . '))';
        }

        return 'TRIM(' . $quotedColumn . ')';
    }

    private function columnMaxLength(string $table, string $column): ?int
    {
        $length = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('character_maximum_length');

        return is_numeric($length) ? (int) $length : null;
    }

    private function hasUniqueIndexForColumn(string $table, string $column): bool
    {
        $indexes = $this->getTableIndexes($table);

        foreach ($indexes as $meta) {
            if ($meta['non_unique'] === 0 && $meta['columns'] === [$column]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{non_unique:int,columns:array<int, string>}>
     */
    private function getTableIndexes(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $rows = DB::select('SHOW INDEX FROM ' . $this->quoteIdentifier($table));
        $indexes = [];

        foreach ($rows as $row) {
            $name = (string) $row->Key_name;
            $seq = (int) $row->Seq_in_index;
            $column = (string) $row->Column_name;
            $indexes[$name]['non_unique'] = (int) $row->Non_unique;
            $indexes[$name]['columns'][$seq] = $column;
        }

        foreach ($indexes as $name => $meta) {
            ksort($meta['columns']);
            $indexes[$name]['columns'] = array_values($meta['columns']);
        }

        return $indexes;
    }

    /**
     * @return array{non_unique:int,columns:array<int, string>}|null
     */
    private function getIndexMetaByName(string $table, string $indexName): ?array
    {
        $indexes = $this->getTableIndexes($table);

        return $indexes[$indexName] ?? null;
    }

    private function dropIndexByName(string $table, string $indexName): void
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedIndex = $this->quoteIdentifier($indexName);
        DB::statement("ALTER TABLE {$quotedTable} DROP INDEX {$quotedIndex}");
    }

    private function quoteIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }
}
