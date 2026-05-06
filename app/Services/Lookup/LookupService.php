<?php

namespace App\Services\Lookup;

use App\Models\Finance\Account;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class LookupService
{
    public const CACHE_KEY_ACCOUNT_STATUSES = 'lookups:account_statuses:v1';
    public const CACHE_KEY_ORDER_STATUSES = 'lookups:order_statuses:v1';
    public const CACHE_KEY_PAYMENT_STATUSES = 'lookups:payment_statuses:v1';

    public function accountStatuses(): array
    {
        return $this->codes('account_statuses', Account::STATUSES, self::CACHE_KEY_ACCOUNT_STATUSES);
    }

    public function orderStatuses(): array
    {
        return $this->codes('order_statuses', Order::STATUSES, self::CACHE_KEY_ORDER_STATUSES);
    }

    public static function forgetAccountStatusesCache(): void
    {
        Cache::forget(self::CACHE_KEY_ACCOUNT_STATUSES);
    }

    public static function forgetOrderStatusesCache(): void
    {
        Cache::forget(self::CACHE_KEY_ORDER_STATUSES);
    }

    public static function forgetPaymentStatusesCache(): void
    {
        Cache::forget(self::CACHE_KEY_PAYMENT_STATUSES);
    }

    private function codes(string $table, array $fallback, string $cacheKey): array
    {
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($table, $fallback): array {
            try {
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    return $fallback;
                }

                $codes = DB::table($table)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->pluck('code')
                    ->filter(fn($v) => is_string($v) && trim($v) !== '')
                    ->map(fn($v) => trim((string) $v))
                    ->values()
                    ->all();

                return count($codes) > 0 ? $codes : $fallback;
            } catch (Throwable) {
                return $fallback;
            }
        });
    }
}
