<?php

namespace Tests\Feature;

use App\Models\Customer\Workshop;
use App\Models\Workshop\WorkshopServiceOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkshopMarketplaceExecutionInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Workshop marketplace tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 940],
            [
                'owner_name' => 'Supplier Owner 940',
                'business_name' => 'Supplier 940',
                'commercial_reg_number' => 'CR-940',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-940',
                'license_image' => null,
                'national_id_number' => 'NID-940',
                'national_id_image' => null,
                'phone' => '779094000',
                'whatsapp' => '779094001',
                'address' => 'Supplier Address',
                'gps_location' => '15.11,44.11',
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

        DB::table('branches')->updateOrInsert(
            ['id' => 94011],
            [
                'supplier_id' => 940,
                'name' => 'Branch 94011',
                'phone' => '779094111',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address A',
                'gps_location' => '15.12,44.12',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 94012],
            [
                'supplier_id' => 940,
                'name' => 'Branch 94012',
                'phone' => '779094112',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address B',
                'gps_location' => '15.13,44.13',
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
                'name' => 'Workshop Product A',
                'model' => 'WPA',
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
            ['id' => 94042],
            [
                'supplier_id' => 940,
                'category_id' => 94021,
                'name' => 'Workshop Product B',
                'model' => 'WPB',
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
                'retail_price' => 130,
                'conversion_factor' => 1,
                'stock_quantity' => 200,
                'low_stock_threshold' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_units')->updateOrInsert(
            ['id' => 94052],
            [
                'product_id' => 94042,
                'unit_id' => 94031,
                'wholesale_price' => 150,
                'retail_price' => 190,
                'conversion_factor' => 1,
                'stock_quantity' => 200,
                'low_stock_threshold' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['id' => 94061],
            [
                'branch_id' => 94011,
                'product_id' => 94041,
                'product_unit_id' => 94051,
                'quantity' => 60,
                'selling_price' => 120,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['id' => 94062],
            [
                'branch_id' => 94012,
                'product_id' => 94042,
                'product_unit_id' => 94052,
                'quantity' => 50,
                'selling_price' => 180,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_accounts')->updateOrInsert(
            ['id' => 940001],
            [
                'customer_id' => null,
                'name' => 'Workshop 940001',
                'phone' => '779094901',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'working_hours' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_accounts')->updateOrInsert(
            ['id' => 940002],
            [
                'customer_id' => null,
                'name' => 'Workshop 940002',
                'phone' => '779094902',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'working_hours' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_workshop_marketplace_cart_checkout_creates_purchase_orders_grouped_by_branch(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        $this->post(route('workshop.marketplace.cart.add'), [
            'stock_id' => 94061,
            'quantity' => 2,
        ])->assertRedirect();

        $this->post(route('workshop.marketplace.cart.add'), [
            'stock_id' => 94062,
            'quantity' => 3,
        ])->assertRedirect();

        $this->post(route('workshop.marketplace.cart.checkout'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('workshop_purchase_orders', [
            'workshop_id' => 940001,
            'supplier_branch_id' => 94011,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('workshop_purchase_orders', [
            'workshop_id' => 940001,
            'supplier_branch_id' => 94012,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('workshop_purchase_order_items', [
            'branch_product_stock_id' => 94061,
            'product_id' => 94041,
            'product_unit_id' => 94051,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('workshop_purchase_order_items', [
            'branch_product_stock_id' => 94062,
            'product_id' => 94042,
            'product_unit_id' => 94052,
            'quantity' => 3,
        ]);
    }

    public function test_update_used_products_recalculates_service_order_totals(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        $order = WorkshopServiceOrder::query()->create([
            'workshop_id' => 940001,
            'service_id' => null,
            'appointment_id' => null,
            'order_number' => 'WSO-940001',
            'customer_name' => 'Customer One',
            'customer_phone' => '779094777',
            'service_fee' => 5000,
            'products_total' => 0,
            'total_amount' => 5000,
            'status' => 'in_progress',
            'notes' => null,
        ]);

        $this->patch(route('workshop.execution.products.update', $order), [
            'used_products_text' => "Oil Filter | 1 | 1500\nEngine Oil | 2 | 1200",
        ])->assertRedirect()->assertSessionHas('status');

        $order->refresh();

        $this->assertSame('3900.00', (string) $order->products_total);
        $this->assertSame('8900.00', (string) $order->total_amount);
        $this->assertIsArray($order->used_products);
        $this->assertCount(2, $order->used_products);
    }

    public function test_invoice_page_is_accessible_for_owner_workshop_and_forbidden_for_others(): void
    {
        $ownerWorkshop = Workshop::query()->findOrFail(940001);
        $anotherWorkshop = Workshop::query()->findOrFail(940002);

        $order = WorkshopServiceOrder::query()->create([
            'workshop_id' => 940001,
            'service_id' => null,
            'appointment_id' => null,
            'order_number' => 'WSO-940002',
            'customer_name' => 'Invoice Customer',
            'customer_phone' => '779094888',
            'service_fee' => 6000,
            'products_total' => 2500,
            'total_amount' => 8500,
            'status' => 'completed',
            'notes' => 'Invoice note',
            'used_products' => [
                [
                    'product_name' => 'Brake Fluid',
                    'quantity' => 1,
                    'unit_cost' => 2500,
                    'line_total' => 2500,
                ],
            ],
        ]);

        $this->actingAs($ownerWorkshop, 'workshop');
        $this->get(route('workshop.sales.invoice', $order))
            ->assertOk()
            ->assertSee('WSO-940002')
            ->assertSee('Invoice Customer');

        $this->actingAs($anotherWorkshop, 'workshop');
        $this->get(route('workshop.sales.invoice', $order))
            ->assertForbidden();
    }

    public function test_workshop_can_store_service_package_definition(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        $this->post(route('workshop.services.store'), [
            'name' => 'Basic Care Package',
            'description' => 'Package for periodic maintenance',
            'price' => 12000,
            'duration_minutes' => 90,
            'requires_products' => 1,
            'is_package' => 1,
            'package_items' => "Oil Change\nFilter Replacement",
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('workshop_services', [
            'workshop_id' => 940001,
            'name' => 'Basic Care Package',
            'is_package' => 1,
        ]);
    }

    public function test_workshop_auto_schedule_creates_appointment_without_manual_datetime(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        DB::table('workshop_services')->updateOrInsert(
            ['id' => 94081],
            [
                'workshop_id' => 940001,
                'name' => 'Diagnostic',
                'description' => null,
                'price' => 2500,
                'duration_minutes' => 60,
                'requires_products' => 0,
                'is_package' => 0,
                'package_items' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('workshop_appointments')->insert([
            'workshop_id' => 940001,
            'service_id' => 94081,
            'customer_name' => 'Existing Booking',
            'customer_phone' => '779094555',
            'vehicle_details' => 'Sedan',
            'appointment_at' => now()->addMinutes(30)->seconds(0),
            'estimated_minutes' => 60,
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('workshop.appointments.store'), [
            'service_id' => 94081,
            'customer_name' => 'Auto Scheduled Customer',
            'customer_phone' => '779094556',
            'vehicle_details' => 'SUV',
            'estimated_minutes' => 60,
            'auto_schedule' => 1,
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('workshop_appointments', [
            'workshop_id' => 940001,
            'customer_name' => 'Auto Scheduled Customer',
            'status' => 'scheduled',
        ]);
    }

    public function test_workshop_maintenance_history_shows_completed_vehicle_orders(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        $this->post(route('workshop.orders.service.store'), [
            'customer_name' => 'Vehicle Owner',
            'customer_phone' => '779094333',
            'vehicle_plate_number' => 'A-12345',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_production_year' => 2020,
            'odometer_km' => 123000,
            'service_fee' => 8000,
            'products_total' => 2000,
            'notes' => 'Periodic service',
        ])->assertRedirect()->assertSessionHas('status');

        $order = WorkshopServiceOrder::query()
            ->where('workshop_id', 940001)
            ->where('customer_phone', '779094333')
            ->latest('id')
            ->firstOrFail();

        $this->patch(route('workshop.orders.service.status', $order), [
            'status' => 'completed',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('workshop_service_orders', [
            'id' => $order->id,
            'status' => 'completed',
            'vehicle_plate_number' => 'A-12345',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
        ]);

        $this->get(route('workshop.maintenance.history'))
            ->assertOk()
            ->assertSee('سجل الصيانة')
            ->assertSee('A-12345')
            ->assertSee('Toyota')
            ->assertSee('Corolla');
    }

    public function test_workshop_unified_api_overview_and_maintenance_history_contract(): void
    {
        $workshop = Workshop::query()->findOrFail(940001);
        $this->actingAs($workshop, 'workshop');

        $this->post(route('workshop.orders.service.store'), [
            'customer_name' => 'API Vehicle Owner',
            'customer_phone' => '779094334',
            'vehicle_plate_number' => 'B-54321',
            'vehicle_brand' => 'Hyundai',
            'vehicle_model' => 'Elantra',
            'vehicle_production_year' => 2019,
            'odometer_km' => 98000,
            'service_fee' => 7000,
            'products_total' => 3000,
        ])->assertRedirect();

        $order = WorkshopServiceOrder::query()
            ->where('workshop_id', 940001)
            ->where('customer_phone', '779094334')
            ->latest('id')
            ->firstOrFail();

        $this->patch(route('workshop.orders.service.status', $order), [
            'status' => 'completed',
        ])->assertRedirect();

        $this->getJson('/api/v1/workshop/overview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('portal', 'workshop')
            ->assertJsonStructure([
                'success',
                'portal',
                'data' => [
                    'actor' => ['id', 'name', 'phone'],
                    'metrics' => ['service_orders_count', 'completed_orders_count', 'service_orders_total'],
                ],
                'meta' => ['timestamp'],
            ]);

        $this->getJson('/api/v1/workshop/maintenance/history')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('portal', 'workshop')
            ->assertJsonPath('data.items.0.vehicle_plate_number', 'B-54321')
            ->assertJsonPath('data.items.0.vehicle_brand', 'Hyundai')
            ->assertJsonPath('data.items.0.vehicle_model', 'Elantra');
    }
}
