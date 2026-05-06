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
        $defaultWorkingHours = WorkingHoursCodec::encode(WorkingHoursCodec::defaultSchedule());

        $this->fillEmptyWorkingHours('suppliers', $defaultWorkingHours);
        $this->fillEmptyWorkingHours('customers', $defaultWorkingHours);
        $this->fillEmptyWorkingHours('branches', $defaultWorkingHours);
        $this->fillEmptyWorkingHours('workshop_accounts', $defaultWorkingHours);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data-only migration. No down operation.
    }

    private function fillEmptyWorkingHours(string $table, string $defaultValue): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'working_hours')) {
            return;
        }

        DB::table($table)
            ->whereNull('working_hours')
            ->orWhere('working_hours', '')
            ->update(['working_hours' => $defaultValue]);
    }
};
