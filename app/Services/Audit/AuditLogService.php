<?php

namespace App\Services\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditLogService
{
    /**
     * @var array<string, mixed>
     */
    private static array $requestContext = [];

    /**
     * @param array<string, mixed> $context
     */
    public static function setRequestContext(array $context): void
    {
        self::$requestContext = $context;
    }

    public static function clearRequestContext(): void
    {
        self::$requestContext = [];
    }

    public function logModelCreated(Model $model): void
    {
        $table = $model->getTable();

        if (! $this->shouldLogTable($table)) {
            return;
        }

        $newValues = $this->sanitizeValues($model->getAttributes());

        $this->insertAuditRow([
            'user_id' => $this->resolveUserId(),
            'event_type' => $this->classifyEventType('created', $table, $newValues),
            'table_name' => $table,
            'record_id' => $this->resolveRecordId($model),
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => $this->resolveIpAddress(),
            'user_agent' => $this->resolveUserAgent(),
            'device' => $this->resolveDevice(),
            'created_at' => now(),
        ]);
    }

    public function logModelUpdated(Model $model): void
    {
        $table = $model->getTable();

        if (! $this->shouldLogTable($table)) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        if (array_keys($changes) === ['deleted_at']) {
            // Soft-delete updates are tracked by deleted event to avoid duplicates.
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $oldValues = $this->sanitizeValues($old);
        $newValues = $this->sanitizeValues($changes);

        $this->insertAuditRow([
            'user_id' => $this->resolveUserId(),
            'event_type' => $this->classifyEventType('updated', $table, $newValues),
            'table_name' => $table,
            'record_id' => $this->resolveRecordId($model),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->resolveIpAddress(),
            'user_agent' => $this->resolveUserAgent(),
            'device' => $this->resolveDevice(),
            'created_at' => now(),
        ]);
    }

    public function logModelDeleted(Model $model): void
    {
        $table = $model->getTable();

        if (! $this->shouldLogTable($table)) {
            return;
        }

        $oldValues = $this->sanitizeValues($model->getOriginal());

        $this->insertAuditRow([
            'user_id' => $this->resolveUserId(),
            'event_type' => $this->classifyEventType('deleted', $table, $oldValues),
            'table_name' => $table,
            'record_id' => $this->resolveRecordId($model),
            'old_values' => $oldValues,
            'new_values' => null,
            'ip_address' => $this->resolveIpAddress(),
            'user_agent' => $this->resolveUserAgent(),
            'device' => $this->resolveDevice(),
            'created_at' => now(),
        ]);
    }

    private function shouldLogTable(string $table): bool
    {
        return ! in_array($table, ['audit_logs', 'audit_trails', 'admin_audit_logs'], true);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitizeValues(array $values): array
    {
        $sensitive = [
            'password',
            'remember_token',
            'token',
            'current_password',
            'new_password',
            'password_confirmation',
        ];

        foreach ($sensitive as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function classifyEventType(string $baseEvent, string $table, array $values): string
    {
        $financialTables = ['payments', 'order_payments', 'workshop_order_payments', 'transactions', 'accounts', 'customer_accounts'];
        $permissionTables = ['portal_account_permissions', 'admin_permission_role', 'admin_role_admin', 'admin_permissions', 'admin_roles'];

        if (in_array($table, $permissionTables, true) || $this->hasPermissionHint($values)) {
            return 'permission_change';
        }

        if (in_array($table, $financialTables, true)) {
            return 'financial_operation';
        }

        if ($this->hasPriceHint($table, $values)) {
            return 'price_change';
        }

        if ($this->hasStockHint($table, $values)) {
            return 'stock_change';
        }

        return match ($baseEvent) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            default => 'updated',
        };
    }

    /**
     * @param array<string, mixed> $values
     */
    private function hasPriceHint(string $table, array $values): bool
    {
        $priceTables = ['products', 'product_units', 'product_variant_units', 'branch_product_stocks', 'pos_local_products'];
        $priceColumns = ['price', 'wholesale_price', 'retail_price', 'selling_price', 'unit_price', 'total_price'];

        if (! in_array($table, $priceTables, true)) {
            return false;
        }

        return $this->containsAnyKey($values, $priceColumns);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function hasStockHint(string $table, array $values): bool
    {
        $stockTables = ['inventory_movements', 'branch_stock_movements', 'branch_product_stocks', 'product_units', 'pos_local_products'];
        $stockColumns = ['stock_quantity', 'quantity', 'stock_before', 'stock_after', 'low_stock_threshold', 'local_quantity'];

        if (! in_array($table, $stockTables, true)) {
            return false;
        }

        return $this->containsAnyKey($values, $stockColumns);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function hasPermissionHint(array $values): bool
    {
        $permissionColumns = ['permission', 'guard_name', 'role', 'role_id', 'permission_id', 'is_granted'];

        return $this->containsAnyKey($values, $permissionColumns);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<int, string> $needles
     */
    private function containsAnyKey(array $values, array $needles): bool
    {
        foreach ($needles as $key) {
            if (array_key_exists($key, $values)) {
                return true;
            }
        }

        return false;
    }

    private function resolveUserId(): ?int
    {
        $contextUserId = self::$requestContext['user_id'] ?? null;
        if (is_int($contextUserId) && $contextUserId > 0) {
            return $contextUserId;
        }

        foreach (['admin', 'agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return (int) Auth::guard($guard)->id();
            }
        }

        return null;
    }

    private function resolveIpAddress(): ?string
    {
        $value = self::$requestContext['ip_address'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function resolveUserAgent(): ?string
    {
        $value = self::$requestContext['user_agent'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function resolveDevice(): ?string
    {
        $value = self::$requestContext['device'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function resolveRecordId(Model $model): ?int
    {
        $key = $model->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function insertAuditRow(array $payload): void
    {
        try {
            AuditLog::query()->create($payload);
        } catch (Throwable) {
            // Audit must never break business transactions.
        }
    }
}
