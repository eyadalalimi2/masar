<?php

namespace Tests\Feature;

use App\Models\Distribution\BranchAccount;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchWorkflowAndUsersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Branch workflow tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 910],
            [
                'owner_name' => 'مالك الفرع',
                'business_name' => 'مؤسسة الفرع',
                'commercial_reg_number' => 'CR-910',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-910',
                'license_image' => null,
                'national_id_number' => 'NID-910',
                'national_id_image' => null,
                'phone' => '779009100',
                'whatsapp' => '779009101',
                'address' => 'عنوان المورد',
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
            ['id' => 9101],
            [
                'supplier_id' => 910,
                'name' => 'Agent 9101',
                'email' => 'agent9101@example.test',
                'phone' => '779009111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 91001],
            [
                'supplier_id' => 910,
                'name' => 'Branch A',
                'phone' => '779009201',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان A',
                'gps_location' => '15.34,44.21',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 91002],
            [
                'supplier_id' => 910,
                'name' => 'Branch B',
                'phone' => '779009202',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان B',
                'gps_location' => '15.34,44.20',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_accounts')->updateOrInsert(
            ['id' => 910001],
            [
                'branch_id' => 91001,
                'name' => 'Branch User A',
                'phone' => '779009201',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_accounts')->updateOrInsert(
            ['id' => 910002],
            [
                'branch_id' => 91002,
                'name' => 'Branch User B',
                'phone' => '779009202',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 91011],
            [
                'supplier_id' => 910,
                'branch_id' => 91001,
                'name' => 'Distributor A',
                'phone' => '779009401',
                'password' => Hash::make('123456'),
                'image' => null,
                'vehicle_type' => 'bike',
                'distribution_points' => 'Zone A',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 91012],
            [
                'supplier_id' => 910,
                'branch_id' => 91001,
                'name' => 'Distributor B',
                'phone' => '779009402',
                'password' => Hash::make('123456'),
                'image' => null,
                'vehicle_type' => 'bike',
                'distribution_points' => 'Zone B',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 91001],
            [
                'name' => 'Category 91001',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 91001],
            [
                'name' => 'قطعة-91001',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 910001],
            [
                'supplier_id' => 910,
                'category_id' => 91001,
                'name' => 'منتج فرع تجريبي',
                'model' => 'M-910',
                'production_year_from' => null,
                'production_year_to' => null,
                'car_models' => null,
                'description' => null,
                'image' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_units')->updateOrInsert(
            ['id' => 9100001],
            [
                'product_id' => 910001,
                'unit_id' => 91001,
                'wholesale_price' => 100,
                'retail_price' => 120,
                'conversion_factor' => 1,
                'stock_quantity' => 0,
                'low_stock_threshold' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => 91001, 'product_unit_id' => 9100001],
            [
                'product_id' => 910001,
                'quantity' => 1,
                'selling_price' => 130,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9100011],
            [
                'supplier_id' => 910,
                'branch_id' => 91001,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 91001,
                'customer_name' => 'عميل تجريبي',
                'customer_phone' => '779009501',
                'customer_address' => 'عنوان العميل',
                'total_price' => 15000,
                'status' => 'approved',
                'distributor_stage' => null,
                'created_by' => 9101,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9100012],
            [
                'supplier_id' => 910,
                'branch_id' => 91001,
                'distributor_id' => 91011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 91001,
                'customer_name' => 'عميل ضغط عمل',
                'customer_phone' => '779009502',
                'customer_address' => 'عنوان إضافي',
                'total_price' => 10000,
                'status' => 'assigned',
                'distributor_stage' => 'assigned',
                'created_by' => 9101,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_branch_can_assign_then_reject_order(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        $this->patch(route('branch.orders.assign-distributor', 9100011), [
            'distributor_id' => 91011,
        ])->assertRedirect();

        $assigned = Order::query()->findOrFail(9100011);
        $this->assertSame(91011, (int) $assigned->distributor_id);
        $this->assertSame('assigned', $assigned->status);
        $this->assertSame('assigned', $assigned->distributor_stage);

        $this->patch(route('branch.orders.reject', 9100011), [
            'reason' => 'اختبار الرفض',
        ])->assertRedirect();

        $rejected = Order::query()->findOrFail(9100011);
        $this->assertSame('cancelled', $rejected->status);
    }

    public function test_unassigning_distributor_reverts_status_to_approved(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        $this->patch(route('branch.orders.assign-distributor', 9100011), [
            'distributor_id' => 91011,
        ])->assertRedirect();

        $assigned = Order::query()->findOrFail(9100011);
        $this->assertSame('assigned', $assigned->status);
        $this->assertSame(91011, (int) $assigned->distributor_id);

        $this->patch(route('branch.orders.assign-distributor', 9100011), [
            'distributor_id' => null,
        ])->assertRedirect();

        $unassigned = Order::query()->findOrFail(9100011);
        $this->assertNull($unassigned->distributor_id);
        $this->assertNull($unassigned->distributor_stage);
        $this->assertSame('approved', $unassigned->status);
    }

    public function test_branch_users_management_is_scoped_and_self_protected(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $otherBranchUser = BranchAccount::query()->findOrFail(910002);
        $this->actingAs($branchUser, 'branch');

        $this->post(route('branch.users.store'), [
            'name' => 'New Branch User',
            'phone' => '779009777',
            'password' => '123456',
            'password_confirmation' => '123456',
            'status' => 'active',
        ])->assertRedirect();

        $created = BranchAccount::query()->where('phone', '779009777')->firstOrFail();
        $this->assertSame(91001, (int) $created->branch_id);

        $this->put(route('branch.users.update', $branchUser), [
            'name' => $branchUser->name,
            'phone' => $branchUser->phone,
            'status' => 'inactive',
        ])->assertRedirect()->assertSessionHasErrors('branch_users');

        $this->assertSame('active', BranchAccount::query()->findOrFail($branchUser->id)->status);

        $this->patch(route('branch.users.toggle', $otherBranchUser))
            ->assertNotFound();

        $this->delete(route('branch.users.destroy', $branchUser))
            ->assertRedirect()
            ->assertSessionHasErrors('branch_users');

        $this->assertDatabaseHas('branch_accounts', ['id' => $branchUser->id]);
    }

    public function test_branch_smart_dispatch_assigns_best_scored_distributor(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        $this->patch(route('branch.orders.smart-dispatch', 9100011))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => 9100011,
            'distributor_id' => 91012,
            'status' => 'assigned',
        ]);
    }

    public function test_smart_dispatch_assigns_least_loaded_distributor(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        $this->patch(route('branch.orders.smart-dispatch', 9100011))
            ->assertRedirect();

        $order = Order::query()->findOrFail(9100011);
        $this->assertSame(91012, (int) $order->distributor_id);
        $this->assertSame('assigned', $order->status);
    }

    public function test_auto_reorder_creates_request_for_low_stock_items(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        $this->post(route('branch.inventory.auto-reorder'))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_replenishment_requests', [
            'branch_id' => 91001,
            'supplier_id' => 910,
            'product_id' => 910001,
            'product_unit_id' => 9100001,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 9101,
            'title' => 'طلب توريد تلقائي من الفرع',
        ]);
    }

    public function test_delay_alerts_are_generated_for_stale_orders(): void
    {
        $branchUser = BranchAccount::query()->findOrFail(910001);
        $this->actingAs($branchUser, 'branch');

        DB::table('orders')
            ->where('id', 9100011)
            ->update([
                'status' => 'approved',
                'updated_at' => now()->subHours(10),
            ]);

        $this->post(route('branch.orders.delay-alerts.generate'))
            ->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'branch_account',
            'recipient_id' => 910001,
            'title' => 'تنبيه تأخير الطلبات',
            'body' => 'الطلب #9100011 متأخر ويحتاج متابعة فورية.',
        ]);
    }
}
