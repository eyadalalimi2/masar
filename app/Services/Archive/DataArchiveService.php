<?php

namespace App\Services\Archive;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataArchiveService
{
    /**
     * @var array<string, string>
     */
    private array $archiveTables = [
        'audit_logs' => 'audit_logs_archive',
        'inventory_movements' => 'inventory_movements_archive',
        'order_status_histories' => 'order_status_histories_archive',
        'distributor_location_logs' => 'distributor_location_logs_archive',
    ];

    /**
     * @param array<int, string> $onlyTables
     * @return array<string, array<string, int|string>>
     */
    public function archive(array $onlyTables = [], bool $dryRun = false): array
    {
        $retentionDays = (array) config('archive.retention_days', []);
        $batchSize = max(100, (int) config('archive.batch_size', 1000));
        $maxBatchesPerTable = max(1, (int) config('archive.max_batches_per_table', 20));

        $targets = $this->resolveTargets($onlyTables);
        $summary = [];

        foreach ($targets as $table) {
            $days = (int) ($retentionDays[$table] ?? 0);
            if ($days <= 0) {
                $summary[$table] = [
                    'archived' => 0,
                    'deleted_from_live' => 0,
                    'status' => 'skipped_no_retention',
                ];

                continue;
            }

            $cutoff = CarbonImmutable::now()->subDays($days);
            $tableSummary = [
                'archived' => 0,
                'deleted_from_live' => 0,
                'status' => 'ok',
                'retention_days' => $days,
                'batch_size' => $batchSize,
            ];

            for ($batch = 0; $batch < $maxBatchesPerTable; $batch++) {
                $rows = $this->baseQuery($table, $cutoff)
                    ->orderBy('id')
                    ->limit($batchSize)
                    ->get();

                if ($rows->isEmpty()) {
                    break;
                }

                if ($dryRun) {
                    $tableSummary['archived'] += $rows->count();
                    $tableSummary['deleted_from_live'] += $rows->count();
                    continue;
                }

                $batchResult = $this->archiveBatch($table, $rows);
                $tableSummary['archived'] += $batchResult['archived'];
                $tableSummary['deleted_from_live'] += $batchResult['deleted_from_live'];
            }

            $summary[$table] = $tableSummary;
        }

        return $summary;
    }

    /**
     * @param array<int, string> $onlyTables
     * @return array<string, int>
     */
    public function cleanupArchives(array $onlyTables = [], bool $dryRun = false): array
    {
        $cleanupDays = (array) config('archive.archive_cleanup_days', []);
        $targets = $this->resolveTargets($onlyTables);
        $deleted = [];

        foreach ($targets as $table) {
            $days = (int) ($cleanupDays[$table] ?? 0);
            if ($days <= 0) {
                $deleted[$table] = 0;
                continue;
            }

            $archiveTable = $this->archiveTable($table);
            $cutoff = CarbonImmutable::now()->subDays($days);

            if ($dryRun) {
                $deleted[$table] = DB::table($archiveTable)
                    ->where('archived_at', '<', $cutoff)
                    ->count();
                continue;
            }

            $deleted[$table] = DB::table($archiveTable)
                ->where('archived_at', '<', $cutoff)
                ->delete();
        }

        return $deleted;
    }

    public function restore(string $table, int $sourceId): bool
    {
        if (! isset($this->archiveTables[$table])) {
            return false;
        }

        $archiveTable = $this->archiveTable($table);

        return DB::transaction(function () use ($table, $archiveTable, $sourceId): bool {
            $row = DB::table($archiveTable)->where('source_id', $sourceId)->first();
            if (! $row) {
                return false;
            }

            $payload = $this->archiveRowToLivePayload((array) $row);
            $payload['id'] = $sourceId;

            $inserted = DB::table($table)->insertOrIgnore($payload);
            if ((int) $inserted === 0) {
                return false;
            }

            DB::table($archiveTable)->where('source_id', $sourceId)->delete();

            return true;
        });
    }

    private function archiveTable(string $table): string
    {
        return (string) $this->archiveTables[$table];
    }

    private function baseQuery(string $table, CarbonImmutable $cutoff)
    {
        $query = DB::table($table)->where('created_at', '<', $cutoff);

        if ($table === 'inventory_movements') {
            $query->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('branch_stock_movements')
                    ->whereColumn('branch_stock_movements.inventory_movement_id', 'inventory_movements.id');
            });
        }

        return $query;
    }

    /**
     * @param Collection<int, object> $rows
     * @return array{archived:int,deleted_from_live:int}
     */
    private function archiveBatch(string $table, Collection $rows): array
    {
        $archiveTable = $this->archiveTable($table);
        $now = CarbonImmutable::now();
        $payload = [];

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $rowArray['source_id'] = (int) $rowArray['id'];
            unset($rowArray['id']);
            $rowArray['archived_at'] = $now;
            $payload[] = $rowArray;
        }

        DB::table($archiveTable)->insertOrIgnore($payload);

        $sourceIds = collect($rows)->pluck('id')->map(fn($id) => (int) $id)->values()->all();

        $archivedSourceIds = DB::table($archiveTable)
            ->whereIn('source_id', $sourceIds)
            ->pluck('source_id')
            ->map(fn($id) => (int) $id)
            ->all();

        if ($archivedSourceIds === []) {
            return ['archived' => 0, 'deleted_from_live' => 0];
        }

        $deleteQuery = DB::table($table)->whereIn('id', $archivedSourceIds);

        if ($table === 'inventory_movements') {
            $deleteQuery->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('branch_stock_movements')
                    ->whereColumn('branch_stock_movements.inventory_movement_id', 'inventory_movements.id');
            });
        }

        $deleted = $deleteQuery->delete();

        return [
            'archived' => count($archivedSourceIds),
            'deleted_from_live' => (int) $deleted,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function archiveRowToLivePayload(array $row): array
    {
        unset($row['id'], $row['source_id'], $row['archived_at']);

        return $row;
    }

    /**
     * @param array<int, string> $onlyTables
     * @return array<int, string>
     */
    private function resolveTargets(array $onlyTables): array
    {
        $all = array_keys($this->archiveTables);
        if ($onlyTables === []) {
            return $all;
        }

        $only = collect($onlyTables)
            ->filter(fn($table) => is_string($table) && trim($table) !== '')
            ->map(fn($table) => trim((string) $table))
            ->unique()
            ->values()
            ->all();

        return array_values(array_intersect($all, $only));
    }
}
