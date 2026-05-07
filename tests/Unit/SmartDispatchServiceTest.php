<?php

namespace Tests\Unit;

use App\Events\Orders\DistributorAutoAssigned;
use App\Models\Orders\Order;
use App\Modules\Delivery\Repositories\SmartDispatchRepositoryInterface;
use App\Modules\Delivery\Services\SmartDispatchService;
use App\Services\Orders\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class SmartDispatchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_auto_assign_for_admin_assigns_distributor_and_dispatches_event(): void
    {
        Event::fake();

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('withCount')->andReturnSelf();
        $query->shouldReceive('selectRaw')->andReturnSelf();
        $query->shouldReceive('orderByRaw')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn((object) [
            'id' => 77,
            'name' => 'Dispatcher 77',
            'active_orders_count' => 3,
            'distance_km' => 1.25,
        ]);

        $repository = Mockery::mock(SmartDispatchRepositoryInterface::class);
        $repository->shouldReceive('customerDestinationFromOrder')->once()->andReturn([33.5, 36.3]);
        $repository->shouldReceive('dispatchCandidatesQuery')->once()->with(10, 5)->andReturn($query);

        $orderService = Mockery::mock(OrderService::class);
        $orderService->shouldReceive('assignDistributor')->once()->withArgs(function (Order $order, int $distributorId): bool {
            return (int) $order->id === 501 && $distributorId === 77;
        });

        $service = new SmartDispatchService($repository, $orderService);

        $order = new Order();
        $order->id = 501;
        $order->supplier_id = 10;
        $order->branch_id = 5;
        $order->customer_address = '33.5000,36.3000';

        $selection = $service->autoAssignForAdmin($order);

        $this->assertNotNull($selection);
        $this->assertSame(77, $selection->distributorId);
        $this->assertSame('Dispatcher 77', $selection->distributorName);
        $this->assertSame(3, $selection->activeOrdersCount);
        $this->assertSame(1.25, $selection->distanceKm);

        Event::assertDispatched(DistributorAutoAssigned::class, function (DistributorAutoAssigned $event): bool {
            return $event->orderId === 501
                && $event->distributorId === 77
                && $event->actor === 'admin'
                && (float) $event->distanceKm === 1.25
                && $event->activeOrdersCount === 3;
        });
    }

    public function test_auto_assign_for_admin_returns_null_when_no_candidate_found(): void
    {
        Event::fake();

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('withCount')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn(null);

        $repository = Mockery::mock(SmartDispatchRepositoryInterface::class);
        $repository->shouldReceive('customerDestinationFromOrder')->once()->andReturn(null);
        $repository->shouldReceive('dispatchCandidatesQuery')->once()->with(10, null)->andReturn($query);

        $orderService = Mockery::mock(OrderService::class);
        $orderService->shouldReceive('assignDistributor')->never();

        $service = new SmartDispatchService($repository, $orderService);

        $order = new Order();
        $order->id = 502;
        $order->supplier_id = 10;
        $order->branch_id = null;
        $order->customer_address = null;

        $selection = $service->autoAssignForAdmin($order);

        $this->assertNull($selection);
        Event::assertNotDispatched(DistributorAutoAssigned::class);
    }
}
