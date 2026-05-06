<?php

namespace Tests\Feature;

use App\Models\Customer\Consumer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsumerWorkflowRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Consumer workflow tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 950],
            [
                'owner_name' => 'Owner 950',
                'business_name' => 'Supplier 950',
                'commercial_reg_number' => 'CR-950',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-950',
                'license_image' => null,
                'national_id_number' => 'NID-950',
                'national_id_image' => null,
                'phone' => '779095000',
                'whatsapp' => '779095001',
                'address' => 'Supplier Address 950',
                'gps_location' => '15.20,44.20',
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
            ['id' => 9501],
            [
                'supplier_id' => 950,
                'name' => 'Agent 9501',
                'email' => 'agent9501@example.test',
                'phone' => '779095101',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 95011],
            [
                'supplier_id' => 950,
                'name' => 'Branch 95011',
                'phone' => '779095111',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address 95011',
                'gps_location' => '15.2001,44.2001',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 95012],
            [
                'supplier_id' => 950,
                'name' => 'Branch 95012',
                'phone' => '779095112',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address 95012',
                'gps_location' => '16.2001,45.2001',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 95021],
            [
                'name' => 'Category 95021',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 95031],
            [
                'name' => 'pcs-95031',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 95041],
            [
                'supplier_id' => 950,
                'category_id' => 95021,
                'name' => 'Consumer Product 95041',
                'model' => 'CP-95041',
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
            ['id' => 95051],
            [
                'product_id' => 95041,
                'unit_id' => 95031,
                'wholesale_price' => 100,
                'retail_price' => 130,
                'conversion_factor' => 1,
                'stock_quantity' => 100,
                'low_stock_threshold' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['id' => 95061],
            [
                'branch_id' => 95011,
                'product_id' => 95041,
                'product_unit_id' => 95051,
                'quantity' => 30,
                'selling_price' => 125,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['id' => 95062],
            [
                'branch_id' => 95012,
                'product_id' => 95041,
                'product_unit_id' => 95051,
                'quantity' => 30,
                'selling_price' => 122,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumers')->updateOrInsert(
            ['id' => 950001],
            [
                'name' => 'Consumer 950001',
                'phone' => '779095901',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'Consumer Main Address',
                'gps_location' => '15.2000,44.2000',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumers')->updateOrInsert(
            ['id' => 950002],
            [
                'name' => 'Consumer Far',
                'phone' => '779095902',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'Far Address',
                'gps_location' => '10.0000,40.0000',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumer_addresses')->updateOrInsert(
            ['id' => 950701],
            [
                'consumer_id' => 950001,
                'label' => 'المنزل',
                'contact_name' => 'Consumer 950001',
                'phone' => '779095901',
                'address_line' => 'Default Delivery Address',
                'gps_location' => '15.2000,44.2000',
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_product_order_respects_pickup_and_delivery_address_behavior(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $this->post(route('consumer.orders.product.store'), [
            'branch_id' => 95011,
            'product_unit_id' => 95051,
            'quantity' => 1,
            'fulfillment' => 'pickup',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'consumer_id' => 950001,
            'branch_id' => 95011,
            'customer_type' => 'b2c',
            'customer_address' => 'استلام من المتجر: Branch 95011',
        ]);

        $this->post(route('consumer.orders.product.store'), [
            'branch_id' => 95011,
            'product_unit_id' => 95051,
            'quantity' => 1,
            'fulfillment' => 'delivery',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'consumer_id' => 950001,
            'branch_id' => 95011,
            'customer_type' => 'b2c',
            'customer_address' => 'Default Delivery Address',
        ]);
    }

    public function test_consumer_cannot_rate_store_without_delivered_order(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $this->post(route('consumer.ratings.store'), [
            'store_type' => 'pos',
            'store_id' => 95011,
            'rating' => 5,
            'review' => 'Great store',
        ])->assertRedirect()->assertSessionHasErrors('rating');

        DB::table('orders')->insert([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('consumer.ratings.store'), [
            'store_type' => 'pos',
            'store_id' => 95011,
            'rating' => 4,
            'review' => 'Good',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('consumer_ratings', [
            'consumer_id' => 950001,
            'store_type' => 'pos',
            'store_id' => 95011,
            'rating' => 4,
        ]);
    }

    public function test_far_consumer_cannot_open_non_nearby_store_view(): void
    {
        $consumer = Consumer::query()->findOrFail(950002);
        $this->actingAs($consumer, 'consumer');

        $this->get(route('consumer.store.show', ['storeType' => 'pos', 'storeId' => 95011]))
            ->assertNotFound();
    }

    public function test_consumer_can_load_advanced_recommendations_feed(): void
    {
        DB::table('orders')->insert([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = (int) DB::table('orders')->where('consumer_id', 950001)->latest('id')->value('id');
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => 95041,
            'product_unit_id' => 95051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 125,
            'total' => 125,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $this->get(route('consumer.recommendations'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'consumer_id',
                'product_recommendations',
                'service_recommendations',
                'generated_at',
            ]);
    }

    public function test_consumer_can_update_and_set_default_address(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        DB::table('consumer_addresses')->insert([
            'id' => 950702,
            'consumer_id' => 950001,
            'label' => 'العمل',
            'contact_name' => 'Consumer 950001',
            'phone' => '779095901',
            'address_line' => 'Work Address',
            'gps_location' => '15.21,44.21',
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->put(route('consumer.addresses.update', 950702), [
            'label' => 'العمل - محدث',
            'contact_name' => 'Receiver Updated',
            'phone' => '779095903',
            'address_line' => 'Updated Work Address',
            'gps_location' => '15.22,44.22',
        ])->assertRedirect()->assertSessionHas('status');

        $this->patch(route('consumer.addresses.default', 950702))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('consumer_addresses', [
            'id' => 950702,
            'label' => 'العمل - محدث',
            'is_default' => 1,
        ]);

        $this->assertDatabaseHas('consumer_addresses', [
            'id' => 950701,
            'is_default' => 0,
        ]);
    }

    public function test_browse_filters_by_radius_and_min_rating(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        DB::table('consumer_ratings')->insert([
            [
                'consumer_id' => 950001,
                'store_type' => 'pos',
                'store_id' => 95011,
                'order_id' => null,
                'rating' => 5,
                'review' => 'near high rating',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'consumer_id' => 950001,
                'store_type' => 'pos',
                'store_id' => 95012,
                'order_id' => null,
                'rating' => 2,
                'review' => 'far low rating',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('consumer.browse', [
            'type' => 'products',
            'radius_km' => 80,
            'min_rating' => 4,
            'sort' => 'distance',
        ]))
            ->assertOk()
            ->assertSee('Branch 95011')
            ->assertDontSee('Branch 95012');
    }

    public function test_home_generates_reorder_prediction_alert_for_due_product(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $firstOrderId = (int) DB::table('orders')->insertGetId([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $firstOrderId,
            'product_id' => 95041,
            'product_unit_id' => 95051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 125,
            'total' => 125,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        $secondOrderId = (int) DB::table('orders')->insertGetId([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $secondOrderId,
            'product_id' => 95041,
            'product_unit_id' => 95051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 125,
            'total' => 125,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        $this->get(route('consumer.home'))
            ->assertOk()
            ->assertSee('توقعات إعادة الطلب')
            ->assertSee('Consumer Product 95041');

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'consumer',
            'recipient_id' => 950001,
            'title' => 'اقتراح إعادة طلب ذكي',
        ]);
    }

    public function test_consumer_dashboard_displays_smart_intelligence_blocks(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        DB::table('web_alerts')->insert([
            'recipient_type' => 'consumer',
            'recipient_id' => 950001,
            'title' => 'تنبيه تجريبي',
            'body' => 'رسالة تنبيه للمستهلك.',
            'data' => null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('consumer.dashboard'))
            ->assertOk()
            ->assertSee('منتجات مقترحة لإعادة الطلب')
            ->assertSee('خدمات مقترحة لإعادة الطلب')
            ->assertSee('التنبيهات الذكية')
            ->assertSee('تنبيه تجريبي');
    }

    public function test_history_marks_due_orders_as_smart_reorder_recommendation(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $oldOrderId = (int) DB::table('orders')->insertGetId([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $oldOrderId,
            'product_id' => 95041,
            'product_unit_id' => 95051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 125,
            'total' => 125,
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ]);

        $recentOrderId = (int) DB::table('orders')->insertGetId([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 125,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $recentOrderId,
            'product_id' => 95041,
            'product_unit_id' => 95051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 125,
            'total' => 125,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        $this->get(route('consumer.history'))
            ->assertOk()
            ->assertSee('مقترح إعادة الطلب')
            ->assertSee('إعادة موصى بها');
    }

    public function test_consumer_can_mark_all_alerts_as_read(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        DB::table('web_alerts')->insert([
            [
                'recipient_type' => 'consumer',
                'recipient_id' => 950001,
                'title' => 'تنبيه 1',
                'body' => 'تفاصيل تنبيه 1',
                'data' => null,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipient_type' => 'consumer',
                'recipient_id' => 950001,
                'title' => 'تنبيه 2',
                'body' => 'تفاصيل تنبيه 2',
                'data' => null,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->post(route('consumer.alerts.read-all'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $unreadCount = (int) DB::table('web_alerts')
            ->where('recipient_type', 'consumer')
            ->where('recipient_id', 950001)
            ->whereNull('read_at')
            ->count();

        $this->assertSame(0, $unreadCount);
    }

    public function test_consumer_can_manage_default_vehicle_from_profile(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $this->post(route('consumer.profile.vehicles.store'), [
            'nickname' => 'Family Car',
            'plate_number' => 'A-111',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'production_year' => 2020,
            'last_odometer_km' => 55000,
            'is_default' => 1,
        ])->assertRedirect()->assertSessionHas('status');

        DB::table('consumer_vehicle_profiles')->insert([
            'id' => 950801,
            'consumer_id' => 950001,
            'nickname' => 'Work Car',
            'plate_number' => 'B-222',
            'brand' => 'Hyundai',
            'model' => 'Elantra',
            'production_year' => 2021,
            'last_odometer_km' => 34000,
            'notes' => null,
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patch(route('consumer.profile.vehicles.default', 950801))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('consumer_vehicle_profiles', [
            'id' => 950801,
            'consumer_id' => 950001,
            'is_default' => 1,
        ]);
    }

    public function test_profile_syncs_loyalty_points_from_orders_and_services(): void
    {
        $consumer = Consumer::query()->findOrFail(950001);
        $this->actingAs($consumer, 'consumer');

        $orderId = (int) DB::table('orders')->insertGetId([
            'supplier_id' => 950,
            'branch_id' => 95011,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => 950001,
            'seller_type' => 'branch',
            'seller_id' => 95011,
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'customer_address' => 'Default Delivery Address',
            'total_price' => 500,
            'payable_total' => 500,
            'status' => 'delivered',
            'distributor_stage' => null,
            'created_by' => 9501,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('workshop_accounts')->updateOrInsert(
            ['id' => 950901],
            [
                'customer_id' => null,
                'name' => 'Workshop 950901',
                'phone' => '779095951',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'working_hours' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $serviceOrderId = (int) DB::table('workshop_service_orders')->insertGetId([
            'workshop_id' => 950901,
            'service_id' => null,
            'appointment_id' => null,
            'order_number' => 'WSO-950001',
            'customer_name' => 'Consumer 950001',
            'customer_phone' => '779095901',
            'service_fee' => 900,
            'products_total' => 100,
            'total_amount' => 1000,
            'payable_total' => 1000,
            'status' => 'completed',
            'notes' => null,
            'used_products' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->get(route('consumer.profile.index'))
            ->assertOk()
            ->assertSee('نقاط الولاء');

        $this->assertDatabaseHas('consumer_loyalty_points', [
            'consumer_id' => 950001,
            'source_type' => 'order',
            'source_id' => $orderId,
            'direction' => 'credit',
        ]);

        $this->assertDatabaseHas('consumer_loyalty_points', [
            'consumer_id' => 950001,
            'source_type' => 'service_order',
            'source_id' => $serviceOrderId,
            'direction' => 'credit',
        ]);
    }
}
