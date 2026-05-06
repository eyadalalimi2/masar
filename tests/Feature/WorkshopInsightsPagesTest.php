<?php

namespace Tests\Feature;

use App\Models\Customer\Workshop;
use App\Models\Workshop\WorkshopService;
use App\Models\Workshop\WorkshopServiceOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkshopInsightsPagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('UAT workshop tests تحتاج قاعدة MySQL لأن بعض migrations تستخدم دوال SQL خاصة بـ MySQL.');
        }
    }

    public function test_workshop_can_open_all_insights_pages(): void
    {
        $workshop = Workshop::query()->updateOrCreate(
            ['phone' => '770000001'],
            [
                'name' => 'ورشة الاختبار',
                'password' => 'secret-123',
                'status' => 'active',
            ]
        );

        $this->actingAs($workshop, 'workshop');

        $this->get(route('workshop.execution.index'))->assertOk();
        $this->get(route('workshop.sales.index'))->assertOk();
        $this->get(route('workshop.pricing.index'))->assertOk();
        $this->get(route('workshop.customers.index'))->assertOk();
        $this->get(route('workshop.reports.index'))->assertOk();
    }

    public function test_reports_and_pricing_use_real_order_data(): void
    {
        $workshop = Workshop::query()->updateOrCreate(
            ['phone' => '770000002'],
            [
                'name' => 'ورشة البيانات',
                'password' => 'secret-123',
                'status' => 'active',
            ]
        );

        $service = WorkshopService::query()->updateOrCreate(
            [
                'workshop_id' => $workshop->id,
                'name' => 'تغيير زيت',
            ],
            [
                'price' => 7000,
                'duration_minutes' => 30,
                'requires_products' => true,
                'is_active' => true,
            ]
        );

        WorkshopServiceOrder::query()->updateOrCreate(
            ['order_number' => 'WSO-000001'],
            [
                'workshop_id' => $workshop->id,
                'service_id' => $service->id,
                'customer_name' => 'عميل أول',
                'customer_phone' => '771111111',
                'service_fee' => 7000,
                'products_total' => 4500,
                'total_amount' => 11500,
                'status' => 'completed',
            ]
        );

        WorkshopServiceOrder::query()->updateOrCreate(
            ['order_number' => 'WSO-000002'],
            [
                'workshop_id' => $workshop->id,
                'service_id' => $service->id,
                'customer_name' => 'عميل ثان',
                'customer_phone' => '772222222',
                'service_fee' => 7000,
                'products_total' => 3000,
                'total_amount' => 10000,
                'status' => 'completed',
            ]
        );

        $this->actingAs($workshop, 'workshop');

        $this->get(route('workshop.reports.index'))
            ->assertOk()
            ->assertSee('تغيير زيت')
            ->assertSee('21,500.00', false);

        $this->get(route('workshop.pricing.index'))
            ->assertOk()
            ->assertSee('تغيير زيت')
            ->assertSee('2');
    }

    public function test_execution_page_displays_sla_priority_and_can_generate_sla_alerts(): void
    {
        $workshop = Workshop::query()->updateOrCreate(
            ['phone' => '770000003'],
            [
                'name' => 'ورشة SLA',
                'password' => 'secret-123',
                'status' => 'active',
            ]
        );

        $service = WorkshopService::query()->updateOrCreate(
            [
                'workshop_id' => $workshop->id,
                'name' => 'صيانة دورية',
            ],
            [
                'price' => 9000,
                'duration_minutes' => 60,
                'requires_products' => true,
                'is_active' => true,
            ]
        );

        $order = WorkshopServiceOrder::query()->create([
            'workshop_id' => $workshop->id,
            'service_id' => $service->id,
            'appointment_id' => null,
            'order_number' => 'WSO-SLA-001',
            'customer_name' => 'عميل SLA',
            'customer_phone' => '773333333',
            'service_fee' => 9000,
            'products_total' => 1500,
            'total_amount' => 10500,
            'status' => 'in_progress',
            'notes' => null,
        ]);

        DB::table('workshop_service_orders')
            ->where('id', $order->id)
            ->update([
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ]);

        $this->actingAs($workshop, 'workshop');

        $this->get(route('workshop.execution.index'))
            ->assertOk()
            ->assertSee('قائمة أولوية التنفيذ الذكية (SLA)')
            ->assertSee('WSO-SLA-001');

        $this->post(route('workshop.execution.sla-alerts.generate'))
            ->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'workshop_account',
            'recipient_id' => $workshop->id,
            'title' => 'تنبيه SLA لطلبات الخدمة',
        ]);

        DB::table('workshop_service_orders')->where('id', $order->id)->delete();
    }
}
