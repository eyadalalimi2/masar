<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNotificationsCenterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Admin notifications tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('admins')->updateOrInsert(
            ['id' => 960001],
            [
                'name' => 'Admin 960001',
                'phone' => '779096001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('suppliers')->updateOrInsert(
            ['id' => 9601],
            [
                'owner_name' => 'Owner 9601',
                'business_name' => 'Supplier 9601',
                'commercial_reg_number' => 'CR-9601',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-9601',
                'license_image' => null,
                'national_id_number' => 'NID-9601',
                'national_id_image' => null,
                'phone' => '779096101',
                'whatsapp' => '779096102',
                'address' => 'Supplier Address 9601',
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
            ['id' => 96011],
            [
                'supplier_id' => 9601,
                'name' => 'Agent 96011',
                'email' => 'agent96011@example.test',
                'phone' => '779096111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_admin_can_publish_broadcast_to_suppliers(): void
    {
        $admin = Admin::query()->findOrFail(960001);
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.notifications.store'), [
            'title' => 'تنبيه مهم',
            'message' => 'محتوى التنبيه',
            'target_type' => 'suppliers',
            'is_active' => 1,
            'send_mode' => 'now',
        ])->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseHas('admin_broadcasts', [
            'title' => 'تنبيه مهم',
            'target_type' => 'suppliers',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 96011,
            'title' => 'تنبيه مهم',
            'body' => 'محتوى التنبيه',
        ]);
    }

    public function test_admin_can_mark_notification_as_read(): void
    {
        $admin = Admin::query()->findOrFail(960001);
        $this->actingAs($admin, 'admin');

        DB::table('web_alerts')->insert([
            'recipient_type' => 'admin',
            'recipient_id' => 960001,
            'title' => 'Admin Alert',
            'body' => 'Unread alert',
            'data' => null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $alertId = (int) DB::table('web_alerts')
            ->where('recipient_type', 'admin')
            ->where('recipient_id', 960001)
            ->latest('id')
            ->value('id');

        $this->patch(route('admin.notifications.mark-read', $alertId))
            ->assertRedirect();

        $this->assertDatabaseMissing('web_alerts', [
            'id' => $alertId,
            'read_at' => null,
        ]);
    }

    public function test_scheduled_broadcast_can_be_dispatched_from_panel(): void
    {
        $admin = Admin::query()->findOrFail(960001);
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.notifications.store'), [
            'title' => 'رسالة مجدولة',
            'message' => 'تنفيذ لاحق',
            'target_type' => 'suppliers',
            'is_active' => 1,
            'send_mode' => 'scheduled',
            'scheduled_for' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.notifications.index'));

        $broadcastId = (int) DB::table('admin_broadcasts')
            ->where('title', 'رسالة مجدولة')
            ->latest('id')
            ->value('id');

        $this->post(route('admin.notifications.broadcasts.dispatch', $broadcastId))
            ->assertRedirect();

        $this->assertDatabaseHas('admin_broadcasts', [
            'id' => $broadcastId,
        ]);

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'agent',
            'recipient_id' => 96011,
            'title' => 'رسالة مجدولة',
        ]);
    }

    public function test_smart_alerts_generation_creates_admin_alert(): void
    {
        $admin = Admin::query()->findOrFail(960001);
        $this->actingAs($admin, 'admin');

        DB::table('orders')->insert([
            'supplier_id' => 9601,
            'branch_id' => null,
            'distributor_id' => null,
            'customer_type' => 'b2c',
            'customer_id' => null,
            'consumer_id' => null,
            'seller_type' => 'supplier',
            'seller_id' => 9601,
            'customer_name' => 'Test',
            'customer_phone' => '779000000',
            'customer_address' => 'Addr',
            'total_price' => 100,
            'status' => 'pending',
            'distributor_stage' => null,
            'created_by' => 96011,
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
        ]);

        $this->post(route('admin.notifications.smart-alerts.generate'))
            ->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'admin',
            'recipient_id' => 960001,
            'title' => 'تحذير تشغيل',
        ]);
    }

    public function test_notifications_center_can_filter_delay_type_and_show_daily_summary(): void
    {
        $admin = Admin::query()->findOrFail(960001);
        $this->actingAs($admin, 'admin');

        DB::table('web_alerts')->insert([
            [
                'recipient_type' => 'admin',
                'recipient_id' => 960001,
                'title' => 'تنبيه تأخير طلبات النظام',
                'body' => 'تأخير في الطلبات.',
                'data' => json_encode(['type' => 'admin_order_delay_alert', 'source' => 'orders']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipient_type' => 'admin',
                'recipient_id' => 960001,
                'title' => 'تحذير تشغيل',
                'body' => 'تحذير ذكي.',
                'data' => json_encode(['type' => 'smart_alert', 'source' => 'system']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipient_type' => 'admin',
                'recipient_id' => 960001,
                'title' => 'تحديث توزيع تلقائي',
                'body' => 'تمت إعادة إسناد مندوب.',
                'data' => json_encode(['type' => 'dispatch_rebalance', 'source' => 'dispatch']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('admin.notifications.index', ['type' => 'delay']))
            ->assertOk()
            ->assertSee('تنبيهات التأخير')
            ->assertSee('إجمالي اليوم')
            ->assertSee('تأخير')
            ->assertSee('Orders (اليوم)')
            ->assertSee('Dispatch (اليوم)')
            ->assertSee('System (اليوم)')
            ->assertSee('تنبيه تأخير طلبات النظام')
            ->assertDontSee('تحذير ذكي.');

        $this->get(route('admin.notifications.index', ['source' => 'dispatch']))
            ->assertOk()
            ->assertSee('Dispatch')
            ->assertSee('تحديث توزيع تلقائي')
            ->assertDontSee('تنبيه تأخير طلبات النظام');
    }
}
