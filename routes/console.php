<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Services\Notifications\AdminBroadcastService;
use App\Services\Operations\OperationalMonitoringService;
use App\Models\Security\PortalAccountPermission;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ops:monitor-live', function (OperationalMonitoringService $monitoringService) {
    $snapshot = $monitoringService->snapshot(true);

    $overall = (string) (($snapshot['health']['overall'] ?? 'ok'));
    $metrics = (array) ($snapshot['metrics'] ?? []);

    $this->info('Operational health: ' . strtoupper($overall));
    $this->table(
        ['Metric', 'Value'],
        [
            ['failed_jobs_count', $metrics['failed_jobs_count'] ?? 0],
            ['alerts_last_15m', $metrics['alerts_last_15m'] ?? 0],
            ['active_delivery_now', $metrics['active_delivery_now'] ?? 0],
            ['write_pressure_indicator', $metrics['write_pressure_indicator'] ?? 0],
            ['sla_on_time_percent_30d', $metrics['sla_on_time_percent_30d'] ?? 0],
        ]
    );

    return $overall === 'critical' ? self::FAILURE : self::SUCCESS;
})->purpose('Run unified operational monitoring snapshot and threshold alerts');

Schedule::command('ops:monitor-live')->everyMinute();
Schedule::command('archive:run')->dailyAt('03:15')->withoutOverlapping();

Artisan::command('admin:dispatch-scheduled-notifications', function (AdminBroadcastService $service) {
    $result = $service->queueDueScheduled();

    $this->info('Scheduled broadcasts queued: ' . $result['queued_broadcasts']);
    $this->info('Run queue workers to deliver notifications asynchronously.');

    return self::SUCCESS;
})->purpose('Dispatch due scheduled admin broadcasts to recipients');

Artisan::command('audit:account-customer-links', function () {
    $posMissingCustomerId = DB::table('pos_accounts')
        ->whereNull('customer_id')
        ->count();

    $workshopMissingCustomerId = DB::table('workshop_accounts')
        ->whereNull('customer_id')
        ->count();

    $posInvalidCustomerId = DB::table('pos_accounts as p')
        ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
        ->whereNotNull('p.customer_id')
        ->whereNull('c.id')
        ->count();

    $workshopInvalidCustomerId = DB::table('workshop_accounts as w')
        ->leftJoin('customers as c', 'c.id', '=', 'w.customer_id')
        ->whereNotNull('w.customer_id')
        ->whereNull('c.id')
        ->count();

    $posTypeMismatch = DB::table('pos_accounts as p')
        ->join('customers as c', 'c.id', '=', 'p.customer_id')
        ->where('c.type', '!=', 'retail_store')
        ->count();

    $workshopTypeMismatch = DB::table('workshop_accounts as w')
        ->join('customers as c', 'c.id', '=', 'w.customer_id')
        ->where('c.type', '!=', 'workshop')
        ->count();

    $this->info('Account-Customer Link Audit');
    $this->newLine();

    $this->table(
        ['Check', 'Count'],
        [
            ['POS missing customer_id', $posMissingCustomerId],
            ['Workshop missing customer_id', $workshopMissingCustomerId],
            ['POS invalid customer_id reference', $posInvalidCustomerId],
            ['Workshop invalid customer_id reference', $workshopInvalidCustomerId],
            ['POS linked to non-retail_store customer', $posTypeMismatch],
            ['Workshop linked to non-workshop customer', $workshopTypeMismatch],
        ]
    );

    $totalIssues = $posMissingCustomerId
        + $workshopMissingCustomerId
        + $posInvalidCustomerId
        + $workshopInvalidCustomerId
        + $posTypeMismatch
        + $workshopTypeMismatch;

    if ($totalIssues === 0) {
        $this->info('All account-customer links are consistent.');

        return self::SUCCESS;
    }

    $this->warn('Issues found in account-customer links.');

    $samplePos = DB::table('pos_accounts as p')
        ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
        ->select('p.id', 'p.phone', 'p.customer_id', 'c.type as customer_type')
        ->where(function ($query): void {
            $query->whereNull('p.customer_id')
                ->orWhereNull('c.id')
                ->orWhere('c.type', '!=', 'retail_store');
        })
        ->orderBy('p.id')
        ->limit(10)
        ->get();

    $sampleWorkshop = DB::table('workshop_accounts as w')
        ->leftJoin('customers as c', 'c.id', '=', 'w.customer_id')
        ->select('w.id', 'w.phone', 'w.customer_id', 'c.type as customer_type')
        ->where(function ($query): void {
            $query->whereNull('w.customer_id')
                ->orWhereNull('c.id')
                ->orWhere('c.type', '!=', 'workshop');
        })
        ->orderBy('w.id')
        ->limit(10)
        ->get();

    if ($samplePos->isNotEmpty()) {
        $this->newLine();
        $this->line('Sample POS issues:');
        $this->table(['id', 'phone', 'customer_id', 'customer_type'], $samplePos->map(fn($row) => [
            $row->id,
            $row->phone,
            $row->customer_id,
            $row->customer_type,
        ])->all());
    }

    if ($sampleWorkshop->isNotEmpty()) {
        $this->newLine();
        $this->line('Sample Workshop issues:');
        $this->table(['id', 'phone', 'customer_id', 'customer_type'], $sampleWorkshop->map(fn($row) => [
            $row->id,
            $row->phone,
            $row->customer_id,
            $row->customer_type,
        ])->all());
    }

    return self::FAILURE;
})->purpose('Audit POS/Workshop links to customers using customer_id integrity checks');

Artisan::command('fix:account-customer-links', function () {
    $fixedPos = 0;
    $fixedWorkshop = 0;
    $skippedPos = 0;
    $skippedWorkshop = 0;

    $fixLinks = function (string $accountsTable, string $expectedType, string $alias) use (&$fixedPos, &$fixedWorkshop, &$skippedPos, &$skippedWorkshop): void {
        $rows = DB::table($accountsTable . ' as a')
            ->leftJoin('customers as c', 'c.id', '=', 'a.customer_id')
            ->select('a.id', 'a.phone', 'a.customer_id', 'c.id as linked_customer_id', 'c.type as linked_customer_type')
            ->where(function ($query) use ($expectedType, $alias): void {
                $query->whereNull('a.customer_id')
                    ->orWhereNull('c.id')
                    ->orWhere('c.type', '!=', $expectedType);
            })
            ->orderBy('a.id')
            ->get();

        foreach ($rows as $row) {
            $phone = is_string($row->phone) ? trim($row->phone) : '';
            if ($phone === '') {
                if ($alias === 'pos') {
                    $skippedPos++;
                } else {
                    $skippedWorkshop++;
                }

                continue;
            }

            $customer = DB::table('customers')
                ->where('phone', $phone)
                ->first();

            if (! $customer) {
                $customerId = DB::table('customers')->insertGetId([
                    'type' => $expectedType,
                    'name' => $alias === 'pos' ? ('محل #' . $row->id) : ('ورشة #' . $row->id),
                    'phone' => $phone,
                    'whatsapp' => null,
                    'address' => 'غير محدد',
                    'gps_location' => null,
                    'owner_name' => null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($customer->type !== $expectedType) {
                if ($alias === 'pos') {
                    $skippedPos++;
                } else {
                    $skippedWorkshop++;
                }

                continue;
            } else {
                $customerId = (int) $customer->id;
            }

            DB::table($accountsTable)
                ->where('id', $row->id)
                ->update([
                    'customer_id' => $customerId,
                    'updated_at' => now(),
                ]);

            if ($alias === 'pos') {
                $fixedPos++;
            } else {
                $fixedWorkshop++;
            }
        }
    };

    $fixLinks('pos_accounts', 'retail_store', 'pos');
    $fixLinks('workshop_accounts', 'workshop', 'workshop');

    $this->table(['Metric', 'Value'], [
        ['POS fixed', $fixedPos],
        ['Workshop fixed', $fixedWorkshop],
        ['POS skipped (phone conflicts/missing)', $skippedPos],
        ['Workshop skipped (phone conflicts/missing)', $skippedWorkshop],
    ]);

    if (($skippedPos + $skippedWorkshop) > 0) {
        $this->warn('Some accounts were skipped due to phone conflicts with another customer type or missing phone.');
    }

    $this->info('Link repair finished. Run audit:account-customer-links to verify.');

    return self::SUCCESS;
})->purpose('Repair POS/Workshop customer_id links safely using phone-based backfill with type checks');

Artisan::command('audit:branch-sale-duplicates {--fix : Delete duplicate sale rows and keep the oldest id}', function () {
    $duplicates = DB::table('branch_stock_movements')
        ->select(
            'branch_id',
            'order_id',
            'product_unit_id',
            'movement_type',
            DB::raw('COUNT(*) as rows_count'),
            DB::raw('MIN(id) as keep_id')
        )
        ->where('movement_type', 'sale')
        ->whereNotNull('order_id')
        ->groupBy('branch_id', 'order_id', 'product_unit_id', 'movement_type')
        ->havingRaw('COUNT(*) > 1')
        ->orderByDesc('rows_count')
        ->get();

    if ($duplicates->isEmpty()) {
        $this->info('No duplicate sale movements found.');

        return self::SUCCESS;
    }

    $this->warn('Duplicate sale movement groups detected: ' . $duplicates->count());
    $this->table(
        ['branch_id', 'order_id', 'product_unit_id', 'movement_type', 'rows_count', 'keep_id'],
        $duplicates->map(fn($row) => [
            $row->branch_id,
            $row->order_id,
            $row->product_unit_id,
            $row->movement_type,
            $row->rows_count,
            $row->keep_id,
        ])->all()
    );

    if (! $this->option('fix')) {
        $this->line('Run with --fix to remove duplicates safely (keeps MIN(id) per group).');

        return self::FAILURE;
    }

    $deleted = 0;

    DB::transaction(function () use ($duplicates, &$deleted): void {
        foreach ($duplicates as $row) {
            $deleted += DB::table('branch_stock_movements')
                ->where('branch_id', (int) $row->branch_id)
                ->where('order_id', (int) $row->order_id)
                ->where('product_unit_id', (int) $row->product_unit_id)
                ->where('movement_type', (string) $row->movement_type)
                ->where('id', '!=', (int) $row->keep_id)
                ->delete();
        }
    });

    $this->info('Duplicate cleanup completed. Deleted rows: ' . $deleted);

    return self::SUCCESS;
})->purpose('Audit and optionally clean duplicate branch sale stock movements before enforcing unique constraints');

Artisan::command('permissions:portal-account {guard} {account_id} {permission} {--deny} {--revoke}', function () {
    $guard = trim((string) $this->argument('guard'));
    $accountId = (int) $this->argument('account_id');
    $permission = trim((string) $this->argument('permission'));
    $guardAccountTables = [
        'agent' => 'agents',
        'branch' => 'branch_accounts',
        'distributor' => 'distributor_accounts',
        'customer' => 'customers',
        'consumer' => 'consumers',
        'pos' => 'pos_accounts',
        'workshop' => 'workshop_accounts',
    ];

    if (! in_array($guard, ['agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'], true)) {
        $this->error('Invalid guard. Allowed: agent, branch, distributor, customer, consumer, pos, workshop');

        return self::FAILURE;
    }

    if ($accountId <= 0 || $permission === '') {
        $this->error('account_id and permission are required and must be valid.');

        return self::FAILURE;
    }

    // Allow revoke even for missing accounts to support cleanup of stale rows.
    if (! $this->option('revoke')) {
        $accountTable = $guardAccountTables[$guard] ?? null;
        $accountExists = is_string($accountTable)
            && DB::table($accountTable)->where('id', $accountId)->exists();

        if (! $accountExists) {
            $this->error('Account not found for guard [' . $guard . '] and id [' . $accountId . '].');

            return self::FAILURE;
        }
    }

    if ($this->option('revoke')) {
        PortalAccountPermission::query()
            ->where('guard_name', $guard)
            ->where('account_id', $accountId)
            ->where('permission', $permission)
            ->delete();

        $this->info('Permission override revoked.');

        return self::SUCCESS;
    }

    $grant = ! (bool) $this->option('deny');

    PortalAccountPermission::query()->updateOrCreate(
        [
            'guard_name' => $guard,
            'account_id' => $accountId,
            'permission' => $permission,
        ],
        [
            'is_granted' => $grant,
        ]
    );

    $this->info($grant ? 'Permission granted explicitly.' : 'Permission denied explicitly.');

    return self::SUCCESS;
})->purpose('Manage explicit per-account portal permissions (grant/deny/revoke)');

Artisan::command('fix:mojibake-text {--dry-run : Preview only, do not update rows}', function () {
    $targets = [
        'categories' => ['name'],
        'products' => ['name', 'model', 'description'],
        'units' => ['name'],
        'variant_types' => ['name'],
        'variant_values' => ['value'],
        'suppliers' => ['owner_name', 'business_name', 'address'],
        'branches' => ['name', 'address', 'branch_manager_name'],
        'distributors' => ['name', 'distribution_points'],
        'customers' => ['name', 'address', 'owner_name'],
    ];

    $looksMojibake = static function (?string $value): bool {
        if (! is_string($value) || $value === '') {
            return false;
        }

        return preg_match('/[\x{00D8}\x{00D9}\x{00C3}\x{00D0}]/u', $value) === 1;
    };

    $repairOne = static function (?string $value) use ($looksMojibake): ?string {
        if (! is_string($value) || $value === '' || ! $looksMojibake($value)) {
            return $value;
        }

        $bytes = [];
        $length = mb_strlen($value, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $codepoint = mb_ord($char, 'UTF-8');

            if ($codepoint === false || $codepoint > 255) {
                return $value;
            }

            $bytes[] = chr($codepoint);
        }

        $candidate = @mb_convert_encoding(implode('', $bytes), 'UTF-8', 'UTF-8');

        if (! is_string($candidate) || $candidate === '') {
            return $value;
        }

        if (preg_match('/\p{Arabic}/u', $candidate) !== 1) {
            return $value;
        }

        return $candidate;
    };

    $dryRun = (bool) $this->option('dry-run');
    $updatedRows = 0;
    $updatedFields = 0;

    foreach ($targets as $table => $columns) {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            continue;
        }

        $idColumn = DB::getSchemaBuilder()->hasColumn($table, 'id') ? 'id' : null;
        if ($idColumn === null) {
            continue;
        }

        DB::table($table)->orderBy($idColumn)->chunkById(300, function ($rows) use ($table, $columns, $dryRun, &$updatedRows, &$updatedFields, $repairOne): void {
            foreach ($rows as $row) {
                $updates = [];

                foreach ($columns as $column) {
                    $original = isset($row->{$column}) ? (string) $row->{$column} : null;
                    $fixed = $repairOne($original);

                    if (is_string($original) && is_string($fixed) && $fixed !== $original) {
                        $updates[$column] = $fixed;
                        $updatedFields++;
                    }
                }

                if ($updates === []) {
                    continue;
                }

                $updatedRows++;

                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', (int) $row->id)
                        ->update(array_merge($updates, ['updated_at' => now()]));
                }
            }
        }, 'id');
    }

    if ($dryRun) {
        $this->info('Dry run completed.');
    } else {
        $this->info('Repair completed.');
    }

    $this->table(['Metric', 'Value'], [
        ['Rows affected', $updatedRows],
        ['Fields repaired', $updatedFields],
        ['Mode', $dryRun ? 'dry-run' : 'apply'],
    ]);

    return self::SUCCESS;
})->purpose('Repair mojibake-corrupted Arabic text in key catalog/account tables');
