<?php

namespace App\Console\Commands;

use App\Services\Data\UniqueConstraintMaintenanceService;
use Illuminate\Console\Command;

class FixUniqueDuplicatesCommand extends Command
{
    protected $signature = 'data:dedupe-unique-fields {--apply : Apply updates instead of dry-run} {--with-constraints : Also add missing unique constraints after cleanup}';

    protected $description = 'Detect and clean duplicate values for phone/email/sku/barcode/uuid before enforcing unique constraints.';

    public function handle(UniqueConstraintMaintenanceService $service): int
    {
        $apply = (bool) $this->option('apply');
        $withConstraints = (bool) $this->option('with-constraints');

        $cleanup = $service->cleanupDuplicates(! $apply);

        $this->info($apply ? 'Cleanup applied successfully.' : 'Dry-run completed (no changes were written).');

        $rows = [];
        foreach ($cleanup as $key => $data) {
            $rows[] = [
                $key,
                (int) ($data['duplicate_groups'] ?? 0),
                (int) ($data['duplicates_fixed'] ?? 0),
                (string) ($data['mode'] ?? 'dry-run'),
            ];
        }

        $this->table(['Field', 'Duplicate Groups', 'Rows Updated', 'Mode'], $rows);

        if ($apply && $withConstraints) {
            $constraints = $service->ensureUniqueConstraints();
            $this->newLine();
            $this->info('Unique constraints status:');

            $constraintRows = [];
            foreach ($constraints as $key => $status) {
                $constraintRows[] = [$key, $status];
            }

            $this->table(['Field', 'Constraint Action'], $constraintRows);
        }

        return self::SUCCESS;
    }
}
