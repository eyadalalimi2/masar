<?php

use App\Support\WorkingHoursCodec;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convertTable('suppliers');
        $this->convertTable('customers');
        $this->convertTable('branches');
        $this->convertTable('workshop_accounts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data migration: keep current normalized text format.
    }

    private function convertTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'working_hours')) {
            return;
        }

        DB::table($table)
            ->select(['id', 'working_hours'])
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $converted = $this->convertWorkingHours($row->working_hours);

                    if ($converted === null || $converted === $row->working_hours) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['working_hours' => $converted]);
                }
            });
    }

    private function convertWorkingHours(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '['))) {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            return null;
        }

        $rawSchedule = $decoded['days'] ?? $decoded;
        if (! is_array($rawSchedule) || ! $this->looksLikeSchedule($rawSchedule)) {
            return null;
        }

        return WorkingHoursCodec::encode($rawSchedule);
    }

    private function looksLikeSchedule(array $data): bool
    {
        foreach (WorkingHoursCodec::WEEK_DAYS as $day) {
            if (array_key_exists($day, $data)) {
                return true;
            }
        }

        return false;
    }
};
