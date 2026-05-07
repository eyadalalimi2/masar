<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreArchivedRowCommand extends Command
{
    protected $signature = 'archive:restore {table : audit_logs|inventory_movements|order_status_histories|distributor_location_logs} {source_id : Original row id from live table}';

    protected $description = 'Restore one archived row back to its live table by source_id.';

    public function handle(): int
    {
        /** @var \App\Services\Archive\DataArchiveService $archiveService */
        $archiveService = app('App\\Services\\Archive\\DataArchiveService');

        $table = (string) $this->argument('table');
        $sourceId = (int) $this->argument('source_id');

        $restored = $archiveService->restore($table, $sourceId);

        if (! $restored) {
            $this->error('Unable to restore row. Check table name, source_id, or if target id already exists in live table.');

            return self::FAILURE;
        }

        $this->info('Archived row restored successfully.');

        return self::SUCCESS;
    }
}
