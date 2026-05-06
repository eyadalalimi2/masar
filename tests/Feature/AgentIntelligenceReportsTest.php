<?php

namespace Tests\Feature;

use App\Models\Supplier\Agent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentIntelligenceReportsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Agent intelligence tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 9701],
            [
                'owner_name' => 'Owner 9701',
                'business_name' => 'Supplier 9701',
                'commercial_reg_number' => 'CR-9701',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-9701',
                'license_image' => null,
                'national_id_number' => 'NID-9701',
                'national_id_image' => null,
                'phone' => '779097101',
                'whatsapp' => '779097102',
                'address' => 'Supplier Address 9701',
                'gps_location' => '15.30,44.30',
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
            ['id' => 97011],
            [
                'supplier_id' => 9701,
                'name' => 'Agent 97011',
                'email' => 'agent97011@example.test',
                'phone' => '779097111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 97021],
            [
                'supplier_id' => 9701,
                'name' => 'Branch 97021',
                'phone' => '779097121',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address 97021',
                'gps_location' => '15.301,44.301',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 97031],
            [
                'name' => 'Category 97031',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 97032],
            [
                'name' => 'pcs-97032',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 97041],
            [
                'supplier_id' => 9701,
                'category_id' => 97031,
                'name' => 'Low Demand Product 97041',
                'model' => 'LDP-97041',
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
            ['id' => 97051],
            [
                'product_id' => 97041,
                'unit_id' => 97032,
                'wholesale_price' => 100,
                'retail_price' => 130,
                'conversion_factor' => 1,
                'stock_quantity' => 50,
                'low_stock_threshold' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 97061],
            [
                'type' => 'retail_store',
                'name' => 'Retail 97061',
                'phone' => '779097161',
                'password' => Hash::make('123456'),
                'whatsapp' => null,
                'address' => 'Customer Address 97061',
                'gps_location' => null,
                'owner_name' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 97071],
            [
                'supplier_id' => 9701,
                'branch_id' => 97021,
                'name' => 'Distributor Busy 97071',
                'phone' => '779097171',
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
            ['id' => 97072],
            [
                'supplier_id' => 9701,
                'branch_id' => 97021,
                'name' => 'Distributor Free 97072',
                'phone' => '779097172',
                'password' => Hash::make('123456'),
                'image' => null,
                'vehicle_type' => 'car',
                'distribution_points' => 'Zone B',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->insert([
            [
                'supplier_id' => 9701,
                'branch_id' => 97021,
                'distributor_id' => 97071,
                'customer_type' => 'b2b',
                'customer_id' => 97061,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 97021,
                'customer_name' => 'Retail 97061',
                'customer_phone' => '779097161',
                'customer_address' => 'Area Covered',
                'total_price' => 200,
                'status' => 'delivered',
                'distributor_stage' => 'delivered',
                'created_by' => 97011,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'supplier_id' => 9701,
                'branch_id' => 97021,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => 97061,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 97021,
                'customer_name' => 'Retail 97061',
                'customer_phone' => '779097161',
                'customer_address' => 'Area Uncovered',
                'total_price' => 180,
                'status' => 'pending',
                'distributor_stage' => null,
                'created_by' => 97011,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'supplier_id' => 9701,
                'branch_id' => 97021,
                'distributor_id' => 97071,
                'customer_type' => 'b2b',
                'customer_id' => 97061,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 97021,
                'customer_name' => 'Retail 97061',
                'customer_phone' => '779097161',
                'customer_address' => 'Area Busy Distributor',
                'total_price' => 210,
                'status' => 'out_for_delivery',
                'distributor_stage' => 'out_for_delivery',
                'created_by' => 97011,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ],
        ]);

        $deliveredOrderId = (int) DB::table('orders')
            ->where('supplier_id', 9701)
            ->where('status', 'delivered')
            ->latest('id')
            ->value('id');

        DB::table('order_items')->insert([
            'order_id' => $deliveredOrderId,
            'product_id' => 97041,
            'product_unit_id' => 97051,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 200,
            'total' => 200,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);
    }

    public function test_agent_reports_page_displays_intelligence_sections(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $this->get(route('agent.reports.commercial-stores.index'))
            ->assertOk()
            ->assertSee('توقع المبيعات (30 يوم)')
            ->assertSee('مقارنة أداء الفروع')
            ->assertSee('مناطق غير مغطاة')
            ->assertSee('منتجات منخفضة الطلب');
    }

    public function test_agent_can_generate_low_demand_alerts(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $this->post(route('agent.reports.alerts.low-demand'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 97011,
            'title' => 'انخفاض الطلب على منتج',
        ]);
    }

    public function test_agent_can_generate_delay_alerts_for_stale_orders(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $this->post(route('agent.orders.delay-alerts.generate'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 97011,
            'title' => 'تنبيه تأخير طلبات الوكيل',
        ]);
    }

    public function test_agent_smart_dispatch_assigns_least_loaded_distributor(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $pendingOrderId = (int) DB::table('orders')
            ->where('supplier_id', 9701)
            ->where('status', 'pending')
            ->value('id');

        $this->patch(route('agent.orders.smart-dispatch', $pendingOrderId))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $pendingOrderId,
            'distributor_id' => 97072,
        ]);
    }

    public function test_agent_dashboard_displays_delay_intelligence_and_creates_alert(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $this->get(route('agent.dashboard'))
            ->assertOk()
            ->assertSee('الطلبات المتأخرة')
            ->assertSee('تنبيهات التأخير اليوم');

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 97011,
            'title' => 'تنبيه تأخير على مستوى الوكيل',
        ]);
    }

    public function test_agent_can_access_advanced_forecast_endpoint(): void
    {
        $agent = Agent::query()->findOrFail(97011);
        $this->actingAs($agent, 'agent');

        $this->get(route('agent.reports.forecast.advanced'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'supplier_id',
                'forecast',
                'weekly_trend',
                'confidence_percent',
                'risk_level',
                'generated_at',
            ]);
    }
}
