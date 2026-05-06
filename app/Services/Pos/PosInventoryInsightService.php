<?php

namespace App\Services\Pos;

use App\Models\Pos;
use App\Models\PosLocalProduct;
use App\Models\PosSale;
use App\Models\Notifications\WebAlert;
use App\Services\Notifications\WebAlertService;
use Illuminate\Support\Collection;

class PosInventoryInsightService
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function insightsForPos(Pos $pos): Collection
    {
        $localProducts = PosLocalProduct::query()
            ->with(['product:id,name', 'productUnit:id,low_stock_threshold'])
            ->where('pos_account_id', $pos->id)
            ->where('is_active', true)
            ->get();

        if ($localProducts->isEmpty()) {
            return collect();
        }

        $localProductIds = $localProducts->pluck('id')->all();
        $sales14d = PosSale::query()
            ->where('pos_account_id', $pos->id)
            ->whereIn('pos_local_product_id', $localProductIds)
            ->where('sold_at', '>=', now()->subDays(14))
            ->selectRaw('pos_local_product_id, SUM(quantity) as sold_quantity_14d')
            ->groupBy('pos_local_product_id')
            ->pluck('sold_quantity_14d', 'pos_local_product_id');

        return $localProducts->map(function (PosLocalProduct $localProduct) use ($sales14d): array {
            $sold14d = (float) ($sales14d[$localProduct->id] ?? 0);
            $avgDaily = $sold14d / 14;
            $quantity = (float) $localProduct->local_quantity;
            $threshold = (float) ($localProduct->productUnit?->low_stock_threshold ?? 0);

            $daysToStockout = $avgDaily > 0
                ? round($quantity / $avgDaily, 1)
                : null;

            $recommendedReorderQuantity = max(
                $threshold,
                round(max(($avgDaily * 10) - $quantity, 0), 3)
            );

            return [
                'local_product_id' => (int) $localProduct->id,
                'product_name' => (string) ($localProduct->product?->name ?? 'منتج'),
                'local_quantity' => $quantity,
                'low_stock_threshold' => $threshold,
                'avg_daily_sales_14d' => round($avgDaily, 3),
                'days_to_stockout' => $daysToStockout,
                'recommended_reorder_quantity' => $recommendedReorderQuantity,
                'needs_refill' => ($threshold > 0 && $quantity <= $threshold)
                    || ($daysToStockout !== null && $daysToStockout <= 3),
            ];
        });
    }

    public function generateSmartRefillAlerts(Pos $pos): int
    {
        $insights = $this->insightsForPos($pos)
            ->filter(fn(array $insight) => (bool) $insight['needs_refill']);

        $created = 0;
        $today = now()->toDateString();

        foreach ($insights as $insight) {
            $title = 'تنبيه إعادة تعبئة ذكي';
            $body = 'المنتج ' . $insight['product_name'] . ' يقترب من النفاد. الكمية الحالية: '
                . number_format((float) $insight['local_quantity'], 3)
                . '، والكمية المقترحة للطلب: '
                . number_format((float) $insight['recommended_reorder_quantity'], 3) . '.';

            $alreadyExistsToday = WebAlert::query()
                ->where('recipient_type', 'pos_account')
                ->where('recipient_id', $pos->id)
                ->whereDate('created_at', $today)
                ->where('title', $title)
                ->where('body', $body)
                ->exists();

            if ($alreadyExistsToday) {
                continue;
            }

            $this->webAlertService->create(
                'pos_account',
                (int) $pos->id,
                $title,
                $body,
                [
                    'type' => 'pos_smart_refill_alert',
                    'pos_local_product_id' => (int) $insight['local_product_id'],
                    'product_name' => $insight['product_name'],
                    'days_to_stockout' => $insight['days_to_stockout'],
                    'avg_daily_sales_14d' => $insight['avg_daily_sales_14d'],
                    'recommended_reorder_quantity' => $insight['recommended_reorder_quantity'],
                ]
            );

            $created++;
        }

        return $created;
    }
}
