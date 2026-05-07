<?php

namespace App\Console\Commands;

use App\Services\Archive\DataArchiveService;
use Illuminate\Console\Command;

class RunArchiveMaintenanceCommand extends Command
{
    protected $signature = 'archive:run {--table=* : Run for selected table(s)} {--dry-run : Preview only without moving/deleting rows}';

    protected $description = 'Archive old rows from large tables and cleanup old archive rows.';

    public function handle(DataArchiveService $archiveService): int
    {
        $tables = (array) $this->option('table');
        $dryRun = (bool) $this->option('dry-run');

        $archiveSummary = $archiveService->archive($tables, $dryRun);
        $cleanupSummary = $archiveService->cleanupArchives($tables, $dryRun);

        $this->info($dryRun ? 'Archive dry-run summary:' : 'Archive summary:');

        $rows = [];
        foreach ($archiveSummary as $table => $summary) {
            $rows[] = [
                $table,
                (int) ($summary['archived'] ?? 0),
                (int) ($summary['deleted_from_live'] ?? 0),
                (int) ($cleanupSummary[$table] ?? 0),
                (string) ($summary['status'] ?? 'ok'),
            ];
        }

        $this->table(['Table', 'Archived Rows', 'Deleted From Live', 'Archive Cleanup', 'Status'], $rows);

        return self::SUCCESS;
    }
}
