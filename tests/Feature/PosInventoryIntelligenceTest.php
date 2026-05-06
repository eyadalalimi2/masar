<?php

namespace Tests\Feature;

use App\Models\Pos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosInventoryIntelligenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('POS intelligence tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 940],
            [
                'owner_name' => 'مالك POS ذكاء',
                'business_name' => 'توريدات POS ذكاء',
                'commercial_reg_number' => 'CR-940',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-940',
                'license_image' => null,
                'national_id_number' => 'NID-940',
                'national_id_image' => null,
                'phone' => '779094000',
                'whatsapp' => '779094001',
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
            ['id' => 9401],
            [
                'supplier_id' => 940,
                'name' => 'Agent 9401',
                'email' => 'agent9401@example.test',
                'phone' => '779094101',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 94001],
            [
                'type' => 'retail_store',
                'name' => 'POS Customer 94001',
                'phone' => '779094201',
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
            ['id' => 940001],
            [
                'customer_id' => 94001,
                'name' => 'POS 940001',
                'phone' => '779094301',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 94011],
            [
                'supplier_id' => 940,
                'name' => 'Branch 94011',
                'phone' => '779094401',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع',
                'gps_location' => '15.30,44.10',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 94021],
            [
                'name' => 'Category 94021',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 94031],
            [
                'name' => 'pcs-94031',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 94041],
            [
                'supplier_id' => 940,
                'category_id' => 94021,
                'name' => 'Product X',
                'model' => 'PX',
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
            ['id' => 94051],
            [
                'product_id' => 94041,
                'unit_id' => 94031,
                'wholesale_price' => 100,
                'retail_price' => 125,
                'conversion_factor' => 1,
                'stock_quantity' => 100,
                'low_stock_threshold' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('pos_local_products')->updateOrInsert(
            ['id' => 94061],
            [
                'pos_account_id' => 940001,
                'branch_id' => 94011,
                'product_id' => 94041,
                'product_unit_id' => 94051,
                'purchase_price' => 100,
                'selling_price' => 150,
                'local_quantity' => 2,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('pos_sales')->insert([
            [
                'pos_account_id' => 940001,
                'pos_local_product_id' => 94061,
                'order_id' => null,
                'product_name' => 'Product X',
                'customer_name' => null,
                'customer_phone' => null,
                'sale_channel' => 'offline',
                'quantity' => 1,
                'unit_price' => 150,
                'total_amount' => 150,
                'profit_amount' => 50,
                'note' => null,
                'sold_at' => now()->subDays(2),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'pos_account_id' => 940001,
                'pos_local_product_id' => 94061,
                'order_id' => null,
                'product_name' => 'Product X',
                'customer_name' => null,
                'customer_phone' => null,
                'sale_channel' => 'offline',
                'quantity' => 2,
                'unit_price' => 150,
                'total_amount' => 300,
                'profit_amount' => 100,
                'note' => null,
                'sold_at' => now()->subDay(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function test_pos_can_generate_smart_refill_alerts_from_catalog(): void
    {
        $pos = Pos::query()->findOrFail(940001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.catalog.smart-refill.generate'))
            ->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'pos_account',
            'recipient_id' => 940001,
            'title' => 'تنبيه إعادة تعبئة ذكي',
        ]);
    }

    public function test_sale_triggers_smart_refill_alert_generation(): void
    {
        $pos = Pos::query()->findOrFail(940001);
        $this->actingAs($pos, 'pos');

        $this->post(route('pos.sales.store'), [
            'pos_local_product_id' => 94061,
            'quantity' => 1,
            'sale_channel' => 'offline',
        ])->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'pos_account',
            'recipient_id' => 940001,
            'title' => 'تنبيه إعادة تعبئة ذكي',
        ]);
    }

    public function test_dashboard_displays_predicted_stockout_card(): void
    {
        $pos = Pos::query()->findOrFail(940001);
        $this->actingAs($pos, 'pos');

        $this->get(route('pos.dashboard'))
            ->assertOk()
            ->assertSee('أصناف مهددة بالنفاد');
    }
}
