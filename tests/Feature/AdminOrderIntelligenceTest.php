<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrderIntelligenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Admin order intelligence tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('admins')->updateOrInsert(
            ['id' => 965001],
            [
                'name' => 'Admin 965001',
                'phone' => '779965001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('suppliers')->updateOrInsert(
            ['id' => 9651],
            [
                'owner_name' => 'Owner 9651',
                'business_name' => 'Supplier 9651',
                'commercial_reg_number' => 'CR-9651',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-9651',
                'license_image' => null,
                'national_id_number' => 'NID-9651',
                'national_id_image' => null,
                'phone' => '779965101',
                'whatsapp' => '779965102',
                'address' => 'Supplier Address 9651',
                'gps_location' => '15.25,44.25',
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
            ['id' => 96511],
            [
                'supplier_id' => 9651,
                'name' => 'Agent 96511',
                'email' => 'agent96511@example.test',
                'phone' => '779965111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branches')->updateOrInsert(
            ['id' => 96521],
            [
                'supplier_id' => 9651,
                'name' => 'Branch 96521',
                'phone' => '779965121',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'Branch Address 96521',
                'gps_location' => '15.26,44.26',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 96531],
            [
                'supplier_id' => 9651,
                'branch_id' => 96521,
                'name' => 'Distributor Busy 96531',
                'phone' => '779965131',
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
            ['id' => 96532],
            [
                'supplier_id' => 9651,
                'branch_id' => 96521,
                'name' => 'Distributor Free 96532',
                'phone' => '779965132',
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
                'id' => 9651001,
                'supplier_id' => 9651,
                'branch_id' => 96521,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 96521,
                'customer_name' => 'Customer Delay',
                'customer_phone' => '779965201',
                'customer_address' => 'Area Delay',
                'total_price' => 130,
                'status' => 'pending',
                'distributor_stage' => null,
                'created_by' => 96511,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subHours(12),
            ],
            [
                'id' => 9651002,
                'supplier_id' => 9651,
                'branch_id' => 96521,
                'distributor_id' => 96531,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 96521,
                'customer_name' => 'Customer Busy Dist',
                'customer_phone' => '779965202',
                'customer_address' => 'Area Busy',
                'total_price' => 210,
                'status' => 'out_for_delivery',
                'distributor_stage' => 'out_for_delivery',
                'created_by' => 96511,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
        ]);
    }

    public function test_admin_can_generate_delay_alerts_for_stale_orders(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.orders.delay-alerts.generate'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'admin',
            'recipient_id' => 965001,
            'title' => 'تنبيه تأخير طلبات النظام',
        ]);
    }

    public function test_admin_smart_dispatch_assigns_least_loaded_distributor(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->patch(route('admin.orders.smart-dispatch', 9651001))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => 9651001,
            'distributor_id' => 96532,
        ]);
    }

    public function test_admin_dashboard_displays_delay_intelligence_cards(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('الطلبات المتأخرة')
            ->assertSee('تنبيهات التأخير اليوم')
            ->assertSee('أكثر الطلبات تأخرًا (حرجة)')
            ->assertSee('تفصيل التأخير حسب المرحلة')
            ->assertSee('تأخير مرحلة الوكيل')
            ->assertSee('تأخير مرحلة الفرع')
            ->assertSee('تأخير مرحلة التوصيل');
    }

    public function test_admin_orders_page_can_filter_delayed_only(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.orders.index', ['delayed_only' => 1]))
            ->assertOk()
            ->assertSee('طلبات متأخرة فقط')
            ->assertSee('#9651001')
            ->assertDontSee('#9651002');
    }

    public function test_admin_live_metrics_endpoint_returns_operational_snapshot(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard.live-metrics'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'timestamp',
                'metrics' => [
                    'active_orders_now',
                    'out_for_delivery_now',
                    'delivered_today',
                    'sales_today',
                    'new_users_today',
                    'delayed_orders_now',
                ],
            ]);
    }

    public function test_admin_advanced_metrics_endpoint_returns_bi_and_monitoring_contract(): void
    {
        $admin = Admin::query()->findOrFail(965001);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard.advanced-metrics'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'timestamp',
                'advanced_bi' => [
                    'sales_7d',
                    'sales_growth_percent_7d',
                    'delivered_orders_7d',
                    'orders_growth_percent_7d',
                    'pending_orders_total',
                    'sla_on_time_percent_30d',
                    'customer_growth_30d',
                    'customer_growth_delta_30d',
                ],
                'monitoring' => [
                    'failed_jobs_count',
                    'alerts_last_15m',
                    'active_delivery_now',
                    'write_pressure_indicator',
                ],
                'kpi_contract',
            ]);
    }
}
