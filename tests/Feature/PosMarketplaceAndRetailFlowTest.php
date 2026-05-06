<?php

namespace Tests\Feature;

use App\Models\Customer\Consumer;
use App\Models\Pos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosMarketplaceAndRetailFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('POS workflow tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 930],
            [
                'owner_name' => 'مالك POS',
                'business_name' => 'توريدات POS',
                'commercial_reg_number' => 'CR-930',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-930',
                'license_image' => null,
                'national_id_number' => 'NID-930',
                'national_id_image' => null,
                'phone' => '779093000',
                'whatsapp' => '779093001',
                'address' => 'عنوان المورد',
                'gps_location' => '15.35,44.20',
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
            ['id' => 9301],
            [
                'supplier_id' => 930,
                'name' => 'Agent 9301',
                'email' => 'agent9301@example.test',
                'phone' => '779093101',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 93001],
            [
                'type' => 'retail_store',
                'name' => 'POS Customer 93001',
                'phone' => '779093201',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'عنوان POS',
                'gps_location' => '15.36,44.21',
                'working_hours' => null,
                'owner_name' => null,
                'owner_image' => null,
                'logo' => null,
                'store_images' => null,
                'national_id_number' => null,
                'national_id_image' => null,
                'commercial_reg_number' => null,
                'commercial_reg_image' => null,
                'license_number' => null,
                'license_image' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('pos_accounts')->updateOrInsert(
            ['id' => 930001],
            [
                'customer_id' => 93001,
                'name' => 'POS 930001',
                'phone' => '779093301',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumers')->updateOrInsert(
            ['id' => 930002],
            [
                'name' => 'Consumer 930002',
                'phone' => '779093302',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'عنوان مستهلك',
                'gps_location' => '15.37,44.22',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 93011],
            [
                'supplier_id' => 930,
                'name' => 'Branch 93011',
                'phone' => '779093401',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع 1',
                'gps_location' => '15.30,44.10',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 93012],
            [
                'supplier_id' => 930,
                'name' => 'Branch 93012',
                'phone' => '779093402',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع 2',
                'gps_location' => '15.31,44.11',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 93021],
            [
                'name' => 'Category 93021',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 93031],
            [
                'name' => 'pcs-93031',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 93041],
            [
                'supplier_id' => 930,
                'category_id' => 93021,
                'name' => 'Product A',
                'model' => 'PA',
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

        DB::table('products')->updateOrInsert(
            ['id' => 93042],
            [
                'supplier_id' => 930,
                'category_id' => 93021,
                'name' => 'Product B',
                'model' => 'PB',
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
            ['id' => 93051],
            [
                'product_id' => 93041,
                'unit_id' => 93031,
                'wholesale_price' => 100,
                'retail_price' => 120,
                'conversion_factor' => 1,
                'stock_quantity' => 100,
                'low_stock_threshold' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_units')->updateOrInsert(
            ['id' => 93052],
            [
                'product_id' => 93042,
                'unit_id' => 93031,
                'wholesale_price' => 200,
                'retail_price' => 230,
                'conversion_factor' => 1,
                'stock_quantity' => 100,
                'low_stock_threshold' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => 93011, 'product_unit_id' => 93051],
            [
                'product_id' => 93041,
                'quantity' => 50,
                'selling_price' => 110,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => 93012, 'product_unit_id' => 93052],
            [
                'product_id' => 93042,
                'quantity' => 40,
                'selling_price' => 210,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_pos_cart_checkout_creates_supply_orders_for_each_branch(): void
    {
        $pos = Pos::query()->findOrFail(930001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.marketplace.cart.add'), [
            'branch_id' => 93011,
            'product_unit_id' => 93051,
            'quantity' => 2,
        ])->assertRedirect();

        $this->post(route('pos.marketplace.cart.add'), [
            'branch_id' => 93012,
            'product_unit_id' => 93052,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('pos.marketplace.cart.checkout'), [
            'note' => 'checkout feature test',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'branch_id' => 93011,
            'customer_type' => 'b2b',
            'customer_id' => 93001,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'branch_id' => 93012,
            'customer_type' => 'b2b',
            'customer_id' => 93001,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('pos_local_products', [
            'pos_account_id' => 930001,
            'branch_id' => 93011,
            'product_unit_id' => 93051,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('pos_local_products', [
            'pos_account_id' => 930001,
            'branch_id' => 93012,
            'product_unit_id' => 93052,
            'is_active' => 1,
        ]);
    }

    public function test_consumer_can_place_online_order_from_retail_pos_store(): void
    {
        DB::table('pos_local_products')->updateOrInsert(
            ['id' => 93061],
            [
                'pos_account_id' => 930001,
                'branch_id' => 93011,
                'product_id' => 93041,
                'product_unit_id' => 93051,
                'purchase_price' => 100,
                'selling_price' => 150,
                'local_quantity' => 5,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $consumer = Consumer::query()->findOrFail(930002);
        $this->actingAs($consumer, 'consumer');

        $this->post(route('consumer.orders.retail.store'), [
            'pos_id' => 930001,
            'pos_local_product_id' => 93061,
            'quantity' => 2,
            'notes' => 'consumer online retail order',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_sales', [
            'pos_account_id' => 930001,
            'pos_local_product_id' => 93061,
            'sale_channel' => 'online',
            'customer_phone' => '779093302',
        ]);

        $localQty = (float) DB::table('pos_local_products')->where('id', 93061)->value('local_quantity');
        $this->assertSame(3.0, $localQty);
    }

    public function test_pos_can_register_quick_sale_without_local_product_binding(): void
    {
        $pos = Pos::query()->findOrFail(930001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.sales.quick.store'), [
            'product_name' => 'Quick Counter Item',
            'quantity' => 2,
            'unit_price' => 75,
            'purchase_unit_price' => 50,
            'sale_channel' => 'offline',
            'customer_name' => 'Walk-in',
            'customer_phone' => '779000000',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_sales', [
            'pos_account_id' => 930001,
            'product_name' => 'Quick Counter Item',
            'sale_channel' => 'offline',
            'total_amount' => 150,
            'profit_amount' => 50,
        ]);
    }

    public function test_pos_quick_sale_applies_percent_campaign_discount_correctly(): void
    {
        $pos = Pos::query()->findOrFail(930001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.sales.quick.store'), [
            'product_name' => 'Discounted Item',
            'quantity' => 2,
            'unit_price' => 100,
            'purchase_unit_price' => 50,
            'sale_channel' => 'offline',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'campaign_code' => 'POS-RAMADAN-10',
            'customer_name' => 'Promo Buyer',
            'customer_phone' => '779010101',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_sales', [
            'pos_account_id' => 930001,
            'product_name' => 'Discounted Item',
            'gross_amount' => 200,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'discount_amount' => 20,
            'campaign_code' => 'POS-RAMADAN-10',
            'total_amount' => 180,
            'profit_amount' => 80,
        ]);
    }

    public function test_marketplace_supplier_comparison_can_be_loaded_for_product_unit(): void
    {
        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => 93012, 'product_unit_id' => 93051],
            [
                'product_id' => 93041,
                'quantity' => 20,
                'selling_price' => 108,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $pos = Pos::query()->findOrFail(930001);
        $this->actingAs($pos, 'pos');

        $this->get(route('pos.marketplace.index', [
            'compare_product_unit_id' => 93051,
            'compare_quantity' => 3,
        ]))
            ->assertOk()
            ->assertSee('مقارنة الموردين لنفس الصنف')
            ->assertSee('Branch 93011')
            ->assertSee('Branch 93012');
    }

    public function test_pos_unified_api_overview_and_recent_sales_contract(): void
    {
        $pos = Pos::query()->findOrFail(930001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.sales.quick.store'), [
            'product_name' => 'API Contract Item',
            'quantity' => 1,
            'unit_price' => 120,
            'purchase_unit_price' => 80,
            'sale_channel' => 'offline',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'campaign_code' => 'API-FIXED-10',
        ])->assertRedirect();

        $this->getJson('/api/v1/pos/overview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('portal', 'pos')
            ->assertJsonStructure([
                'success',
                'portal',
                'data' => [
                    'actor' => ['id', 'name', 'phone'],
                    'metrics' => ['sales_count', 'sales_total', 'profit_total', 'discount_total'],
                ],
                'meta' => ['timestamp'],
            ]);

        $this->getJson('/api/v1/pos/sales/recent')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('portal', 'pos')
            ->assertJsonPath('data.items.0.campaign_code', 'API-FIXED-10')
            ->assertJsonPath('data.items.0.discount_amount', 10.0)
            ->assertJsonPath('data.items.0.total_amount', 110.0);
    }
}
