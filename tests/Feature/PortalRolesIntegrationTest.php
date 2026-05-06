<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Consumer;
use App\Models\Customer\Customer;
use App\Models\Customer\Workshop;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\DistributorAccount;
use App\Models\Orders\Order;
use App\Models\Pos;
use App\Models\Supplier\Agent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalRolesIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Portal integration tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('admins')->updateOrInsert(
            ['id' => 980001],
            [
                'name' => 'Admin 980001',
                'phone' => '779980001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('admin_roles')->updateOrInsert(
            ['slug' => 'super-admin'],
            [
                'name' => 'Portal Integration Super Admin',
                'updated_at' => $now,
            ]
        );

        $superAdminRoleId = (int) DB::table('admin_roles')
            ->where('slug', 'super-admin')
            ->value('id');

        DB::table('admin_role_admin')->updateOrInsert(
            ['admin_id' => 980001, 'role_id' => $superAdminRoleId],
            [
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('suppliers')->updateOrInsert(
            ['id' => 980],
            [
                'owner_name' => 'Owner 980',
                'business_name' => 'Supplier 980',
                'commercial_reg_number' => 'CR-980',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-980',
                'license_image' => null,
                'national_id_number' => 'NID-980',
                'national_id_image' => null,
                'phone' => '779980100',
                'whatsapp' => '779980101',
                'address' => 'Supplier Address 980',
                'gps_location' => '15.30,44.20',
                'email' => null,
                'working_hours' => '8-5',
                'status' => 'active',
                'is_verified' => 1,
                'verified_at' => $now,
                'verified_by_user_id' => null,
                'verification_requested_at' => null,
                'verification_requested_by_user_id' => null,
                'agent_image' => null,
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('agents')->updateOrInsert(
            ['id' => 98011],
            [
                'supplier_id' => 980,
                'name' => 'Agent 98011',
                'email' => 'agent98011@example.test',
                'phone' => '779980111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 98021],
            [
                'supplier_id' => 980,
                'name' => 'Branch 98021',
                'phone' => '779980121',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address 98021',
                'gps_location' => '15.301,44.201',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_accounts')->updateOrInsert(
            ['id' => 980001],
            [
                'branch_id' => 98021,
                'name' => 'Branch Account 980001',
                'phone' => '779980121',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 98031],
            [
                'supplier_id' => 980,
                'branch_id' => 98021,
                'name' => 'Distributor 98031',
                'phone' => '779980131',
                'password' => Hash::make('123456'),
                'image' => null,
                'vehicle_type' => 'bike',
                'distribution_points' => 'Zone A',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributor_accounts')->updateOrInsert(
            ['id' => 980401],
            [
                'distributor_id' => 98031,
                'name' => 'Distributor Account 980401',
                'phone' => '779980401',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 98051],
            [
                'type' => 'retail_store',
                'name' => 'Retail Customer 98051',
                'phone' => '779980511',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'Retail Address',
                'gps_location' => '15.305,44.205',
                'owner_name' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customer_accounts')->updateOrInsert(
            ['customer_id' => 98051],
            [
                'customer_name' => 'Retail Customer 98051',
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('pos_accounts')->updateOrInsert(
            ['id' => 98061],
            [
                'customer_id' => 98051,
                'name' => 'POS 98061',
                'phone' => '779980611',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 98052],
            [
                'type' => 'workshop',
                'name' => 'Workshop Customer 98052',
                'phone' => '779980521',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'Workshop Address',
                'gps_location' => '15.306,44.206',
                'owner_name' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_accounts')->updateOrInsert(
            ['id' => 98071],
            [
                'customer_id' => 98052,
                'name' => 'Workshop 98071',
                'phone' => '779980711',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'working_hours' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumers')->updateOrInsert(
            ['id' => 98081],
            [
                'name' => 'Consumer 98081',
                'phone' => '779980811',
                'password' => Hash::make('123456'),
                'address' => 'Consumer Address',
                'gps_location' => '15.307,44.207',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9801001],
            [
                'supplier_id' => 980,
                'branch_id' => 98021,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => 98051,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 98021,
                'customer_name' => 'Retail Customer 98051',
                'customer_phone' => '779980511',
                'customer_address' => '15.31,44.21',
                'total_price' => 190,
                'status' => 'approved',
                'distributor_stage' => null,
                'created_by' => 98011,
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(8),
            ]
        );
    }

    public function test_advanced_contract_endpoints_are_available_per_role(): void
    {
        $admin = Admin::query()->findOrFail(980001);
        $this->actingAs($admin, 'admin');
        $this->get(route('admin.dashboard.advanced-metrics'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $agent = Agent::query()->findOrFail(98011);
        $this->actingAs($agent, 'agent');
        $this->get(route('agent.reports.forecast.advanced'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $distributor = DistributorAccount::query()->findOrFail(980401);
        $this->actingAs($distributor, 'distributor');
        $this->get(route('distributor.orders.route-optimization'))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $consumer = Consumer::query()->findOrFail(98081);
        $this->actingAs($consumer, 'consumer');
        $this->get(route('consumer.recommendations'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $customer = Customer::query()->findOrFail(98051);
        $this->actingAs($customer, 'customer');
        $this->get(route('customer.dashboard'))->assertOk();

        $pos = Pos::query()->findOrFail(98061);
        $this->actingAs($pos, 'pos');
        $this->get(route('pos.dashboard'))->assertOk();

        $workshop = Workshop::query()->findOrFail(98071);
        $this->actingAs($workshop, 'workshop');
        $this->get(route('workshop.dashboard'))->assertOk();
    }

    public function test_order_lifecycle_integrates_branch_dispatch_and_distributor_execution(): void
    {
        $branchAccount = BranchAccount::query()->findOrFail(980001);
        $this->actingAs($branchAccount, 'branch');

        $this->patch(route('branch.orders.smart-dispatch', 9801001))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::query()->findOrFail(9801001);
        $this->assertSame(98031, (int) $order->distributor_id);

        $distributor = DistributorAccount::query()->findOrFail(980401);
        $this->actingAs($distributor, 'distributor');

        $this->patch(route('distributor.orders.status', 9801001), [
            'status' => 'accepted',
            'note' => 'Order accepted from integration flow',
            'route_sequence' => 1,
        ])->assertRedirect();

        $this->postJson(route('distributor.orders.offline-sync'), [
            'events' => [
                [
                    'type' => 'status_update',
                    'order_id' => 9801001,
                    'stage' => 'picked_up',
                    'client_event_id' => 'integration-980-001',
                    'route_sequence' => 2,
                ],
                [
                    'type' => 'status_update',
                    'order_id' => 9801001,
                    'stage' => 'out_for_delivery',
                    'client_event_id' => 'integration-980-002',
                    'route_sequence' => 3,
                ],
            ],
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('processed', 2);

        $updated = Order::query()->findOrFail(9801001);
        $this->assertSame('out_for_delivery', (string) $updated->distributor_stage);
    }
}
