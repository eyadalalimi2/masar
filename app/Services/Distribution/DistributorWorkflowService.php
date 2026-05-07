<?php

namespace App\Services\Distribution;

use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorLocationLog;
use App\Models\Distribution\DistributorOrderEvent;
use App\Models\Orders\Order;
use App\Services\Notifications\WebAlertService;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorWorkflowService
{
    public const STAGES = [
        'assigned',
        'accepted',
        'picked_up',
        'out_for_delivery',
        'delivered',
    ];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function updateStage(
        Distributor $distributor,
        Order $order,
        string $stage,
        ?string $note = null,
        ?string $deliveryProofImage = null,
        ?string $deliverySignature = null,
        ?int $routeSequence = null,
        string $eventSource = 'live',
    ): Order {
        if (! in_array($stage, self::STAGES, true)) {
            abort(422, 'المرحلة غير صحيحة.');
        }

        // Sequence enforcement is intentionally disabled to allow manual stage override.

        return DB::transaction(function () use ($distributor, $order, $stage, $note, $deliveryProofImage, $deliverySignature, $routeSequence, $eventSource) {
            $order->update(['distributor_stage' => $stage]);

            if ($stage === 'out_for_delivery') {
                $this->orderService->changeStatus($order, 'out_for_delivery');
            }

            if ($stage === 'delivered') {
                $this->orderService->changeStatus($order, 'delivered');
            }

            $eventPayload = [
                'distributor_id' => $distributor->id,
                'order_id' => $order->id,
                'stage' => $stage,
                'note' => $note,
            ];

            if (Schema::hasColumn('distributor_order_events', 'delivery_signature')) {
                $eventPayload['delivery_signature'] = $deliverySignature;
            }

            if (Schema::hasColumn('distributor_order_events', 'route_sequence')) {
                $eventPayload['route_sequence'] = $routeSequence;
            }

            if (Schema::hasColumn('distributor_order_events', 'event_source')) {
                $eventPayload['event_source'] = in_array($eventSource, ['live', 'offline'], true) ? $eventSource : 'live';
            }

            if (Schema::hasColumn('distributor_order_events', 'delivery_proof_image')) {
                $eventPayload['delivery_proof_image'] = $deliveryProofImage;
            }

            if (Schema::hasColumn('distributor_order_events', 'proof_captured_at')) {
                $eventPayload['proof_captured_at'] = ($deliveryProofImage || $deliverySignature) ? now() : null;
            }

            DistributorOrderEvent::query()->create($eventPayload);

            $this->notifyBranchOnStageChange($order, $stage);

            return $order->fresh(['branch.account']);
        });
    }

    public function recordLocation(
        Distributor $distributor,
        ?Order $order,
        float $latitude,
        float $longitude,
        ?float $accuracyMeters = null,
        ?string $note = null,
    ): DistributorLocationLog {
        $latitude = max(-90, min(90, $latitude));
        $longitude = max(-180, min(180, $longitude));

        $locationExpression = DB::raw(sprintf('ST_GeomFromText("POINT(%F %F)")', $longitude, $latitude));

        $logId = (int) DB::table('distributor_location_logs')->insertGetId([
            'distributor_id' => $distributor->id,
            'order_id' => $order?->id,
            'location' => $locationExpression,
            'accuracy_meters' => $accuracyMeters,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = DistributorLocationLog::query()->withCoordinates()->findOrFail($logId);

        if ($order && $order->branch_id) {
            $branchAccountId = (int) ($order->branch?->account?->id ?? 0);
            if ($branchAccountId > 0) {
                $this->webAlertService->create(
                    'branch_account',
                    $branchAccountId,
                    'تحديث موقع المندوب',
                    'تم إرسال موقع جديد للمندوب على الطلب #' . $order->id,
                    [
                        'type' => 'distributor_location_update',
                        'order_id' => $order->id,
                        'distributor_id' => $distributor->id,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]
                );
            }
        }

        return $log;
    }

    private function notifyBranchOnStageChange(Order $order, string $stage): void
    {
        $branchAccountId = (int) ($order->branch?->account?->id ?? 0);
        if ($branchAccountId <= 0) {
            return;
        }

        $stageLabel = match ($stage) {
            'accepted' => 'تم قبول الطلب من المندوب',
            'picked_up' => 'تم استلام الطلب من الفرع',
            'out_for_delivery' => 'الطلب خرج للتوصيل',
            'delivered' => 'تم تسليم الطلب',
            default => 'تحديث على الطلب',
        };

        $this->webAlertService->create(
            'branch_account',
            $branchAccountId,
            'تحديث حالة طلب المندوب',
            $stageLabel . ' #' . $order->id,
            [
                'type' => 'distributor_stage_update',
                'order_id' => $order->id,
                'stage' => $stage,
            ]
        );
    }
}
