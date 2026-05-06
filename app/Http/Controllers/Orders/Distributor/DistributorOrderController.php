<?php

namespace App\Http\Controllers\Orders\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorAccount;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Services\Lookup\LookupService;
use App\Services\Notifications\WebAlertService;
use App\Services\Distribution\DistributorWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorOrderController extends Controller
{
    public function __construct(
        private readonly DistributorWorkflowService $workflowService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function index(Request $request): View
    {
        $distributor = $this->currentDistributor();
        $accountId = (int) (Auth::guard('distributor')->id() ?? 0);
        $lookupService = app(LookupService::class);
        $distributorStages = $this->distributorStages($lookupService);
        $stage = (string) $request->query('stage', 'all');
        if (! in_array($stage, array_merge(['all'], $distributorStages), true)) {
            $stage = 'all';
        }

        $delayedOrdersCount = $this->delayedOrdersQuery($distributor)->count();
        $delayAlertsTodayCount = WebAlert::query()
            ->where('recipient_type', 'distributor_account')
            ->where('recipient_id', $accountId)
            ->whereDate('created_at', now()->toDateString())
            ->where('title', 'تنبيه تأخير التسليم')
            ->count();

        $orders = Order::with(['supplier', 'branch', 'buyer', 'items.product'])
            ->where('distributor_id', $distributor->id)
            ->when($stage !== 'all', fn($query) => $query->where('distributor_stage', $stage))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $routePlan = $this->buildRoutePlan($distributor);

        return view('distributor.orders.index', compact('orders', 'stage', 'delayedOrdersCount', 'delayAlertsTodayCount', 'routePlan'));
    }

    public function generateDelayAlerts(): RedirectResponse
    {
        $distributor = $this->currentDistributor();
        $accountId = (int) (Auth::guard('distributor')->id() ?? 0);
        if ($accountId <= 0) {
            abort(403);
        }

        $today = now()->toDateString();
        $created = 0;

        $orders = $this->delayedOrdersQuery($distributor)->get();
        foreach ($orders as $order) {
            $body = 'الطلب #' . $order->id . ' متأخر عن نافذة التسليم المتوقعة.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'distributor_account')
                ->where('recipient_id', $accountId)
                ->whereDate('created_at', $today)
                ->where('title', 'تنبيه تأخير التسليم')
                ->where('body', $body)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->webAlertService->create(
                'distributor_account',
                $accountId,
                'تنبيه تأخير التسليم',
                $body,
                [
                    'type' => 'distributor_delivery_delay_alert',
                    'order_id' => $order->id,
                    'distributor_id' => $distributor->id,
                    'delay_hours' => (int) now()->diffInHours($order->updated_at),
                    'stage' => $order->distributor_stage,
                ]
            );

            $created++;
        }

        return back()->with('success', 'تم توليد ' . $created . ' تنبيه تأخير للتوصيل.');
    }

    public function show(Order $order): View
    {
        $distributor = $this->currentDistributor();
        abort_unless((int) $order->distributor_id === (int) $distributor->id, 404);

        $order->load([
            'supplier',
            'branch',
            'buyer',
            'items.product',
            'creator',
            'distributorEvents' => fn($query) => $query->latest(),
            'locationLogs' => fn($query) => $query->latest()->limit(12),
        ]);

        return view('distributor.orders.show', compact('order'));
    }

    public function changeStatus(Request $request, Order $order): RedirectResponse
    {
        $distributor = $this->currentDistributor();
        abort_unless((int) $order->distributor_id === (int) $distributor->id, 404);
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'status' => ['required', Rule::in($this->distributorStages($lookupService))],
            'note' => ['nullable', 'string', 'max:1000'],
            'delivery_signature' => ['nullable', 'string', 'max:255'],
            'delivery_proof_image' => ['nullable', 'image', 'max:5120'],
            'route_sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        $proofImagePath = null;
        if ($request->hasFile('delivery_proof_image')) {
            $proofImagePath = $request->file('delivery_proof_image')->store('delivery-proofs', 'public');
        }

        $this->workflowService->updateStage(
            $distributor,
            $order,
            $data['status'],
            $data['note'] ?? null,
            $proofImagePath,
            $data['delivery_signature'] ?? null,
            isset($data['route_sequence']) ? (int) $data['route_sequence'] : null,
            'live',
        );

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    public function syncOfflineEvents(Request $request): JsonResponse
    {
        $distributor = $this->currentDistributor();
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.type' => ['required', 'in:location,status_update'],
            'events.*.order_id' => ['nullable', 'integer'],
            'events.*.stage' => ['nullable', Rule::in($this->distributorStages($lookupService))],
            'events.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'events.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'events.*.accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'events.*.note' => ['nullable', 'string', 'max:500'],
            'events.*.delivery_signature' => ['nullable', 'string', 'max:255'],
            'events.*.route_sequence' => ['nullable', 'integer', 'min:1'],
            'events.*.client_event_id' => ['nullable', 'string', 'max:120'],
            'events.*.occurred_at' => ['nullable', 'date'],
        ]);

        $processed = 0;
        $skipped = 0;

        foreach ($data['events'] as $event) {
            $type = (string) $event['type'];
            $clientEventId = trim((string) ($event['client_event_id'] ?? ''));
            if ($clientEventId !== '') {
                $cacheKey = 'distributor-offline-event:' . $distributor->id . ':' . sha1($clientEventId);
                if (! Cache::add($cacheKey, 1, now()->addHours(12))) {
                    $skipped++;
                    continue;
                }
            }

            $order = null;

            if (isset($event['order_id'])) {
                $order = Order::query()
                    ->where('distributor_id', $distributor->id)
                    ->find((int) $event['order_id']);
            }

            if ($type === 'location') {
                if (! $order || ! isset($event['latitude'], $event['longitude'])) {
                    $skipped++;
                    continue;
                }

                $this->workflowService->recordLocation(
                    $distributor,
                    $order,
                    (float) $event['latitude'],
                    (float) $event['longitude'],
                    isset($event['accuracy_meters']) ? (float) $event['accuracy_meters'] : null,
                    $event['note'] ?? 'Offline sync location',
                );

                $processed++;
                continue;
            }

            if (! $order || ! isset($event['stage'])) {
                $skipped++;
                continue;
            }

            $this->workflowService->updateStage(
                $distributor,
                $order,
                (string) $event['stage'],
                $event['note'] ?? 'Offline sync status update',
                null,
                $event['delivery_signature'] ?? null,
                isset($event['route_sequence']) ? (int) $event['route_sequence'] : null,
                'offline',
            );

            $processed++;
        }

        return response()->json([
            'ok' => true,
            'processed' => $processed,
            'skipped' => $skipped,
        ]);
    }

    private function distributorStages(LookupService $lookupService): array
    {
        $allowed = [
            Order::STATUS_ASSIGNED,
            Order::STATUS_ACCEPTED,
            Order::STATUS_PICKED_UP,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
        ];

        return array_values(array_intersect($lookupService->orderStatuses(), $allowed));
    }

    public function routeOptimization(): JsonResponse
    {
        $distributor = $this->currentDistributor();

        $routePlan = $this->buildRoutePlan($distributor);

        return response()->json([
            'ok' => true,
            'distributor_id' => (int) $distributor->id,
            'items' => $routePlan,
            'count' => $routePlan->count(),
        ]);
    }

    public function updateLocation(Request $request, Order $order): RedirectResponse|Response|JsonResponse
    {
        $distributor = $this->currentDistributor();
        abort_unless((int) $order->distributor_id === (int) $distributor->id, 404);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->workflowService->recordLocation(
            $distributor,
            $order->loadMissing('branch.account'),
            (float) $data['latitude'],
            (float) $data['longitude'],
            isset($data['accuracy_meters']) ? (float) $data['accuracy_meters'] : null,
            $data['note'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'تم إرسال الموقع بنجاح.',
            ]);
        }

        return back()->with('success', 'تم إرسال الموقع بنجاح.');
    }

    private function currentDistributor(): Distributor
    {
        $account = Auth::guard('distributor')->user();
        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        return Distributor::query()->whereKey((int) $account->distributor_id)->firstOrFail();
    }

    private function delayedOrdersQuery(Distributor $distributor)
    {
        $delayHours = max((int) env('DISTRIBUTOR_DELAY_ALERT_HOURS', 2), 1);

        return Order::query()
            ->where('distributor_id', $distributor->id)
            ->whereIn('distributor_stage', ['accepted', 'picked_up', 'out_for_delivery'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->where('updated_at', '<=', now()->subHours($delayHours));
    }

    private function buildRoutePlan(Distributor $distributor)
    {
        $priority = [
            'out_for_delivery' => 1,
            'picked_up' => 2,
            'accepted' => 3,
            'assigned' => 4,
        ];

        $lastPosition = $distributor->orders()
            ->with(['locationLogs' => fn($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->first()?->locationLogs?->first();

        $origin = $lastPosition ? [(float) $lastPosition->latitude, (float) $lastPosition->longitude] : null;

        return Order::query()
            ->with(['buyer'])
            ->where('distributor_id', $distributor->id)
            ->whereIn('distributor_stage', ['accepted', 'picked_up', 'out_for_delivery'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get()
            ->map(function (Order $order) use ($priority, $origin) {
                $stagePriority = $priority[(string) $order->distributor_stage] ?? 99;
                $staleMinutes = (int) $order->updated_at->diffInMinutes(now());
                $distancePenalty = 0.0;

                if ($origin !== null && is_string($order->customer_address) && str_contains($order->customer_address, ',')) {
                    [$lat, $lng] = array_map('trim', explode(',', $order->customer_address, 2));
                    if (is_numeric($lat) && is_numeric($lng)) {
                        $distancePenalty = $this->haversineKm($origin, [(float) $lat, (float) $lng]);
                    }
                }

                $score = ($stagePriority * 1000) + max(0, (200 - $staleMinutes)) + $distancePenalty;

                $order->route_score = round($score, 2);
                $order->estimated_eta_minutes = max(10, min(180, (int) round(15 + ($distancePenalty * 4))));

                return $order;
            })
            ->sortBy(function (Order $order) {
                return (float) ($order->route_score ?? PHP_INT_MAX);
            })
            ->values();
    }

    private function haversineKm(array $origin, array $destination): float
    {
        [$lat1, $lon1] = $origin;
        [$lat2, $lon2] = $destination;

        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * (2 * asin(min(1, sqrt($a))));
    }
}
