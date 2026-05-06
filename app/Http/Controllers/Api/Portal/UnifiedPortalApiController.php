<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\Workshop\WorkshopServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnifiedPortalApiController extends Controller
{
    public function me(Request $request, string $portal): JsonResponse
    {
        $actor = $request->user($portal);

        if (! $actor) {
            return $this->error('غير مصرح.', 401);
        }

        return $this->ok($portal, [
            'actor' => [
                'id' => $actor->id,
                'name' => $actor->name ?? null,
                'phone' => $actor->phone ?? null,
                'status' => $actor->status ?? null,
            ],
        ]);
    }

    public function overview(Request $request, string $portal): JsonResponse
    {
        $actor = $request->user($portal);

        if (! $actor) {
            return $this->error('غير مصرح.', 401);
        }

        return $this->ok($portal, [
            'actor' => [
                'id' => $actor->id,
                'name' => $actor->name ?? null,
                'phone' => $actor->phone ?? null,
            ],
            'metrics' => $this->resolveMetrics($portal, $actor->id),
        ]);
    }

    public function maintenanceHistory(Request $request): JsonResponse
    {
        $workshop = $request->user('workshop');

        if (! $workshop) {
            return $this->error('غير مصرح.', 401);
        }

        $rows = WorkshopServiceOrder::query()
            ->where('workshop_id', $workshop->id)
            ->where('status', 'completed')
            ->where(function ($query) {
                $query->whereNotNull('vehicle_plate_number')
                    ->orWhereNotNull('vehicle_brand')
                    ->orWhereNotNull('vehicle_model');
            })
            ->latest('updated_at')
            ->limit(30)
            ->get([
                'id',
                'order_number',
                'snapshot_customer_name as customer_name',
                'snapshot_customer_phone as customer_phone',
                'vehicle_plate_number',
                'vehicle_brand',
                'vehicle_model',
                'vehicle_production_year',
                'odometer_km',
                'total_amount',
                'updated_at',
            ]);

        return $this->ok('workshop', [
            'items' => $rows,
            'count' => $rows->count(),
        ]);
    }

    public function salesRecent(Request $request): JsonResponse
    {
        $pos = $request->user('pos');

        if (! $pos) {
            return $this->error('غير مصرح.', 401);
        }

        $rows = PosSale::query()
            ->where('pos_account_id', $pos->id)
            ->latest('sold_at')
            ->limit(30)
            ->get([
                'id',
                'product_name',
                'quantity',
                'unit_price',
                'gross_amount',
                'discount_type',
                'discount_value',
                'discount_amount',
                'campaign_code',
                'total_amount',
                'profit_amount',
                'sale_channel',
                'sold_at',
            ]);

        return $this->ok('pos', [
            'items' => $rows,
            'count' => $rows->count(),
        ]);
    }

    private function resolveMetrics(string $portal, int $actorId): array
    {
        return match ($portal) {
            'pos' => [
                'sales_count' => PosSale::query()->where('pos_account_id', $actorId)->count(),
                'sales_total' => (float) PosSale::query()->where('pos_account_id', $actorId)->sum('total_amount'),
                'profit_total' => (float) PosSale::query()->where('pos_account_id', $actorId)->sum('profit_amount'),
                'discount_total' => (float) PosSale::query()->where('pos_account_id', $actorId)->sum('discount_amount'),
            ],
            'workshop' => [
                'service_orders_count' => WorkshopServiceOrder::query()->where('workshop_id', $actorId)->count(),
                'completed_orders_count' => WorkshopServiceOrder::query()->where('workshop_id', $actorId)->where('status', 'completed')->count(),
                'service_orders_total' => (float) WorkshopServiceOrder::query()->where('workshop_id', $actorId)->sum('total_amount'),
            ],
            default => [],
        };
    }

    private function ok(string $portal, array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'portal' => $portal,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'meta' => [
                'timestamp' => now()->toISOString(),
            ],
        ], $status);
    }
}
