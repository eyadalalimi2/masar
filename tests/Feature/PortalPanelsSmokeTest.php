<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalPanelsSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Portal smoke tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 10],
            [
                'owner_name' => 'مالك تجريبي',
                'business_name' => 'مؤسسة تجريبية',
                'commercial_reg_number' => 'CR-10',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-10',
                'license_image' => null,
                'national_id_number' => 'NID-10',
                'national_id_image' => null,
                'phone' => '779001010',
                'whatsapp' => '779001011',
                'address' => 'عنوان تجريبي',
                'gps_location' => '15.33,44.22',
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
            ['id' => 1],
            [
                'supplier_id' => 10,
                'name' => 'Agent 1',
                'email' => 'agent1@example.test',
                'phone' => '779001001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 36],
            [
                'supplier_id' => 10,
                'name' => 'Branch 36',
                'phone' => '779001036',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع 36',
                'gps_location' => '15.34,44.21',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_accounts')->updateOrInsert(
            ['branch_id' => 36],
            [
                'name' => 'Branch Account 36',
                'phone' => '779001036',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 130],
            [
                'supplier_id' => 10,
                'branch_id' => 36,
                'name' => 'Distributor 130',
                'phone' => '779001130',
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
            ['distributor_id' => 130],
            [
                'name' => 'Distributor Account 130',
                'phone' => '779001430',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 5001],
            [
                'type' => 'retail_store',
                'name' => 'Customer 5001',
                'phone' => '779001501',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'عنوان العميل',
                'gps_location' => null,
                'owner_name' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customer_accounts')->updateOrInsert(
            ['customer_id' => 5001],
            [
                'customer_name' => 'Customer 5001',
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('pos_accounts')->updateOrInsert(
            ['id' => 2],
            [
                'customer_id' => 5001,
                'name' => 'POS 2',
                'phone' => '779001502',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('admins')->updateOrInsert(
            ['id' => 50001],
            [
                'name' => 'Admin 50001',
                'phone' => '779005001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 5002],
            [
                'type' => 'workshop',
                'name' => 'Workshop Customer 5002',
                'phone' => '779001502',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'عنوان ورشة',
                'gps_location' => '15.32,44.24',
                'owner_name' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_accounts')->updateOrInsert(
            ['id' => 3],
            [
                'customer_id' => 5002,
                'name' => 'Workshop 3',
                'phone' => '779001503',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'working_hours' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_admin_agent_and_workshop_panels_load(): void
    {
        $admin = \App\Models\Admin::query()->findOrFail(50001);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.dashboard.advanced-metrics'))->assertOk();

        $agent = \App\Models\Supplier\Agent::query()->findOrFail(1);
        $this->actingAs($agent, 'agent');

        $this->get(route('agent.dashboard'))->assertOk();
        $this->get(route('agent.reports.forecast.advanced'))->assertOk();

        $workshop = \App\Models\Customer\Workshop::query()->findOrFail(3);
        $this->actingAs($workshop, 'workshop');

        $this->get(route('workshop.dashboard'))->assertOk();
        $this->get(route('workshop.live.overview'))->assertOk();
    }

    public function test_customer_panel_pages_load(): void
    {
        $customer = \App\Models\Customer\Customer::query()->findOrFail(5001);
        $this->actingAs($customer, 'customer');

        $this->get(route('customer.dashboard'))->assertOk();
        $this->get(route('customer.orders.index'))->assertOk();
        $this->get(route('customer.payments.index'))->assertOk();
        $this->get(route('customer.profile.index'))->assertOk();

        $consumer = \App\Models\Consumer::query()->first();
        if ($consumer) {
            $this->actingAs($consumer, 'consumer');
            $this->get(route('consumer.recommendations'))->assertOk();
        }
    }

    public function test_pos_panel_pages_load(): void
    {
        $pos = \App\Models\Pos::query()->findOrFail(2);
        $this->actingAs($pos, 'pos');

        $this->get(route('pos.dashboard'))->assertOk();
        $this->get(route('pos.marketplace.index'))->assertOk();
        $this->get(route('pos.orders.index'))->assertOk();
        $this->get(route('pos.reports.index'))->assertOk();
    }

    public function test_branch_and_distributor_panels_load(): void
    {
        $branch = \App\Models\Distribution\BranchAccount::query()->where('branch_id', 36)->firstOrFail();
        $this->actingAs($branch, 'branch');

        $this->get(route('branch.dashboard'))->assertOk();
        $this->get(route('branch.orders.index'))->assertOk();
        $this->get(route('branch.reports.index'))->assertOk();

        $distributor = \App\Models\Distribution\DistributorAccount::query()->where('distributor_id', 130)->firstOrFail();
        $this->actingAs($distributor, 'distributor');

        $this->get(route('distributor.dashboard'))->assertOk();
        $this->get(route('distributor.orders.index'))->assertOk();
        $this->get(route('distributor.payments.index'))->assertOk();
        $this->get(route('distributor.orders.route-optimization'))->assertOk();
    }
}
