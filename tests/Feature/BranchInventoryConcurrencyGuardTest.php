<?php

namespace Tests\Feature;

use App\Models\Distribution\Branch;
use App\Models\Orders\Order;
use App\Services\Distribution\BranchInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchInventoryConcurrencyGuardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Branch inventory guard tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 9950],
            [
                'owner_name' => 'مالك اختبار المخزون',
                'business_name' => 'مؤسسة اختبار المخزون',
                'commercial_reg_number' => 'CR-9950',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-9950',
                'license_image' => null,
                'national_id_number' => 'NID-9950',
                'national_id_image' => null,
                'phone' => '779995000',
                'whatsapp' => '779995001',
                'address' => 'عنوان المورد',
                'gps_location' => '15.10,44.10',
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
            ['id' => 99501],
            [
                'supplier_id' => 9950,
                'name' => 'Agent 99501',
                'email' => 'agent99501@example.test',
                'phone' => '779995011',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 995001],
            [
                'supplier_id' => 9950,
                'name' => 'Branch 995001',
                'phone' => '779995021',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع',
                'gps_location' => '15.11,44.11',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 99501],
            [
                'name' => 'Category 99501',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 99501],
            [
                'name' => 'قطعة-99501',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 9950001],
            [
                'supplier_id' => 9950,
                'category_id' => 99501,
                'name' => 'منتج اختبار الخصم',
                'model' => 'M-9950',
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
            ['id' => 99500001],
            [
                'product_id' => 9950001,
                'unit_id' => 99501,
                'wholesale_price' => 100,
                'retail_price' => 120,
                'conversion_factor' => 1,
                'stock_quantity' => 0,
                'low_stock_threshold' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => 995001, 'product_unit_id' => 99500001],
            [
                'product_id' => 9950001,
                'quantity' => 10,
                'selling_price' => 130,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9951001],
            [
                'supplier_id' => 9950,
                'branch_id' => 995001,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 995001,
                'customer_name' => 'عميل اختبار',
                'customer_phone' => '779995061',
                'customer_address' => 'عنوان العميل',
                'total_price' => 390,
                'status' => 'approved',
                'distributor_stage' => null,
                'created_by' => 99501,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('order_items')->updateOrInsert(
            ['id' => 9951101],
            [
                'order_id' => 9951001,
                'product_id' => 9950001,
                'product_unit_id' => 99500001,
                'product_variant_id' => null,
                'quantity' => 3,
                'unit_price' => 130,
                'total' => 390,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_deduct_order_stock_is_idempotent_for_same_order_item(): void
    {
        $branch = Branch::query()->findOrFail(995001);
        $order = Order::query()->with('items')->findOrFail(9951001);
        $service = app(BranchInventoryService::class);

        $service->deductOrderStock($branch, $order);
        $service->deductOrderStock($branch, $order);

        $saleCount = DB::table('branch_stock_movements')
            ->where('branch_id', 995001)
            ->where('order_id', 9951001)
            ->where('product_unit_id', 99500001)
            ->where('movement_type', 'sale')
            ->count();

        $this->assertSame(1, $saleCount);

        $remaining = (float) DB::table('branch_product_stocks')
            ->where('branch_id', 995001)
            ->where('product_unit_id', 99500001)
            ->value('quantity');

        $this->assertSame(7.0, $remaining);
    }
}
