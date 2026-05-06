<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'uuid')) {
                continue;
            }

            $this->ensureUuidInsertTrigger($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $this->dropUuidInsertTrigger($tableName);
        }
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
};
