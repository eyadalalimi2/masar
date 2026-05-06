<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'orders',
        'payments',
        'customers',
        'suppliers',
        'products',
        'transactions',
        'accounts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }

            $this->backfillUuids($tableName);
            $this->setUuidNotNullWhenPossible($tableName);
            $this->ensureUniqueUuidIndex($tableName);
            $this->ensureUuidInsertTrigger($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'uuid')) {
                continue;
            }

            $indexName = $this->uuidIndexName($tableName);
            if ($this->hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->dropUnique($indexName);
                });
            }

            $this->dropUuidInsertTrigger($tableName);

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('uuid');
            });
        }
    }

    private function backfillUuids(string $tableName): void
    {
        DB::table($tableName)
            ->select('id')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($tableName): void {
                foreach ($rows as $row) {
                    DB::table($tableName)
                        ->where('id', (int) $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            }, 'id');
    }

    private function setUuidNotNullWhenPossible(string $tableName): void
    {
        $missing = DB::table($tableName)
            ->whereNull('uuid')
            ->count();

        if ($missing > 0) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `uuid` CHAR(36) NOT NULL',
            str_replace('`', '``', $tableName)
        ));
    }

    private function ensureUniqueUuidIndex(string $tableName): void
    {
        $indexName = $this->uuidIndexName($tableName);

        if ($this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->unique('uuid', $indexName);
        });
    }

    private function ensureUuidInsertTrigger(string $tableName): void
    {
        $triggerName = $this->uuidTriggerName($tableName);
        $this->dropUuidInsertTrigger($tableName);

        DB::unprepared(sprintf(
            "CREATE TRIGGER `%s` BEFORE INSERT ON `%s` FOR EACH ROW BEGIN\n"
                . "  IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN\n"
                . "    SET NEW.`uuid` = %s;\n"
                . "  END IF;\n"
                . "END",
            str_replace('`', '``', $triggerName),
            str_replace('`', '``', $tableName),
            $this->uuidV4SqlExpression()
        ));
    }

    private function dropUuidInsertTrigger(string $tableName): void
    {
        $triggerName = $this->uuidTriggerName($tableName);

        DB::unprepared(sprintf(
            'DROP TRIGGER IF EXISTS `%s`',
            str_replace('`', '``', $triggerName)
        ));
    }

    private function uuidTriggerName(string $tableName): string
    {
        return $tableName . '_set_uuid_before_insert';
    }

    private function uuidV4SqlExpression(): string
    {
        return "LOWER(CONCAT("
            . "HEX(RANDOM_BYTES(4)), '-', "
            . "HEX(RANDOM_BYTES(2)), '-4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', "
            . "ELT(1 + FLOOR(RAND() * 4), '8', '9', 'a', 'b'), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', "
            . "HEX(RANDOM_BYTES(6))"
            . "))";
    }

    private function uuidIndexName(string $tableName): string
    {
        return $tableName . '_uuid_unique';
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        if (! Schema::hasTable($tableName)) {
            return false;
        }

        $rows = DB::select(sprintf('SHOW INDEX FROM `%s`', str_replace('`', '``', $tableName)));

        foreach ($rows as $row) {
            if ((string) $row->Key_name === $indexName) {
                return true;
            }
        }

        return false;
    }
};
