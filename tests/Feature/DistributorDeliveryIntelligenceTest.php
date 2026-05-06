<?php

namespace Tests\Feature;

use App\Models\Distribution\DistributorAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DistributorDeliveryIntelligenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Distributor intelligence tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 920],
            [
                'owner_name' => 'مالك توزيع',
                'business_name' => 'مؤسسة توزيع',
                'commercial_reg_number' => 'CR-920',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-920',
                'license_image' => null,
                'national_id_number' => 'NID-920',
                'national_id_image' => null,
                'phone' => '779009920',
                'whatsapp' => '779009921',
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

        DB::table('branches')->updateOrInsert(
            ['id' => 92001],
            [
                'supplier_id' => 920,
                'name' => 'Branch 920',
                'phone' => '779009922',
                'branch_manager_name' => null,
                'branch_manager_image' => null,
                'branch_manager_password' => null,
                'address' => 'عنوان الفرع',
                'gps_location' => '15.31,44.19',
                'working_hours' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('agents')->updateOrInsert(
            ['id' => 9201],
            [
                'supplier_id' => 920,
                'name' => 'Agent 9201',
                'email' => 'agent9201@example.test',
                'phone' => '779009924',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributors')->updateOrInsert(
            ['id' => 92011],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'name' => 'Distributor 92011',
                'phone' => '779009923',
                'password' => Hash::make('123456'),
                'image' => null,
                'vehicle_type' => 'bike',
                'distribution_points' => 'A-B',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributor_accounts')->updateOrInsert(
            ['id' => 920001],
            [
                'distributor_id' => 92011,
                'name' => 'Distributor Account 920001',
                'phone' => '779009923',
                'password' => '123456',
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9201001],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل تأخير',
                'customer_phone' => '779009930',
                'customer_address' => 'الحي التجاري',
                'total_price' => 12000,
                'status' => 'out_for_delivery',
                'distributor_stage' => 'out_for_delivery',
                'created_by' => 9201,
                'created_at' => $now,
                'updated_at' => now()->subHours(5),
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9201002],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل اليوم 1',
                'customer_phone' => '779009931',
                'customer_address' => 'الحي 1',
                'total_price' => 7000,
                'status' => 'delivered',
                'distributor_stage' => 'delivered',
                'created_by' => 9201,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9201003],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل اليوم 2',
                'customer_phone' => '779009932',
                'customer_address' => 'الحي 2',
                'total_price' => 7100,
                'status' => 'delivered',
                'distributor_stage' => 'delivered',
                'created_by' => 9201,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9201004],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل اليوم 3',
                'customer_phone' => '779009933',
                'customer_address' => 'الحي 3',
                'total_price' => 7200,
                'status' => 'delivered',
                'distributor_stage' => 'delivered',
                'created_by' => 9201,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        foreach ([9201002, 9201003, 9201004] as $orderId) {
            DB::table('distributor_order_events')->insert([
                [
                    'distributor_id' => 92011,
                    'order_id' => $orderId,
                    'stage' => 'accepted',
                    'note' => null,
                    'created_at' => now()->subHours(4),
                    'updated_at' => now()->subHours(4),
                ],
                [
                    'distributor_id' => 92011,
                    'order_id' => $orderId,
                    'stage' => 'out_for_delivery',
                    'note' => null,
                    'created_at' => now()->subHours(3),
                    'updated_at' => now()->subHours(3),
                ],
                [
                    'distributor_id' => 92011,
                    'order_id' => $orderId,
                    'stage' => 'delivered',
                    'note' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function test_distributor_can_generate_delay_alerts_for_stale_orders(): void
    {
        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $this->post(route('distributor.orders.delay-alerts.generate'))
            ->assertRedirect();

        $this->assertDatabaseHas('web_alerts', [
            'recipient_type' => 'distributor_account',
            'recipient_id' => 920001,
            'title' => 'تنبيه تأخير التسليم',
            'body' => 'الطلب #9201001 متأخر عن نافذة التسليم المتوقعة.',
        ]);
    }

    public function test_dashboard_displays_on_time_delivery_metrics(): void
    {
        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $this->get(route('distributor.dashboard'))
            ->assertOk()
            ->assertSee('نسبة الالتزام الزمني')
            ->assertSee('تسليم متأخر (اليوم)');
    }

    public function test_distributor_can_save_pod_metadata_on_status_update(): void
    {
        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $this->patch(route('distributor.orders.status', 9201001), [
            'status' => 'delivered',
            'note' => 'تم التسليم مع توقيع العميل',
            'delivery_signature' => 'Ali Receiver',
            'route_sequence' => 3,
        ])->assertRedirect();

        $expectedEvent = [
            'distributor_id' => 92011,
            'order_id' => 9201001,
            'stage' => 'delivered',
        ];

        if (Schema::hasColumn('distributor_order_events', 'delivery_signature')) {
            $expectedEvent['delivery_signature'] = 'Ali Receiver';
        }

        if (Schema::hasColumn('distributor_order_events', 'route_sequence')) {
            $expectedEvent['route_sequence'] = 3;
        }

        if (Schema::hasColumn('distributor_order_events', 'event_source')) {
            $expectedEvent['event_source'] = 'live';
        }

        $this->assertDatabaseHas('distributor_order_events', $expectedEvent);
    }

    public function test_distributor_can_sync_offline_events_batch(): void
    {
        DB::table('orders')->updateOrInsert(
            ['id' => 9201005],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل أوفلاين',
                'customer_phone' => '779009935',
                'customer_address' => 'الحي 5',
                'total_price' => 8200,
                'status' => 'out_for_delivery',
                'distributor_stage' => 'out_for_delivery',
                'created_by' => 9201,
                'created_at' => now(),
                'updated_at' => now()->subHours(2),
            ]
        );

        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $this->postJson(route('distributor.orders.offline-sync'), [
            'events' => [
                [
                    'type' => 'location',
                    'order_id' => 9201005,
                    'latitude' => 15.3001,
                    'longitude' => 44.2001,
                    'accuracy_meters' => 6.1,
                    'note' => 'offline location ping',
                ],
                [
                    'type' => 'status_update',
                    'order_id' => 9201005,
                    'stage' => 'delivered',
                    'note' => 'offline delivered sync',
                    'delivery_signature' => 'Offline Receiver',
                    'route_sequence' => 4,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('processed', 2);

        $this->assertDatabaseHas('distributor_location_logs', [
            'distributor_id' => 92011,
            'order_id' => 9201005,
        ]);

        $expectedOfflineEvent = [
            'distributor_id' => 92011,
            'order_id' => 9201005,
            'stage' => 'delivered',
        ];

        if (Schema::hasColumn('distributor_order_events', 'delivery_signature')) {
            $expectedOfflineEvent['delivery_signature'] = 'Offline Receiver';
        }

        if (Schema::hasColumn('distributor_order_events', 'event_source')) {
            $expectedOfflineEvent['event_source'] = 'offline';
        }

        $this->assertDatabaseHas('distributor_order_events', $expectedOfflineEvent);
    }

    public function test_distributor_can_load_route_optimization_snapshot(): void
    {
        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $this->get(route('distributor.orders.route-optimization'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'distributor_id',
                'items',
                'count',
            ]);
    }

    public function test_distributor_offline_sync_skips_duplicate_client_event_id(): void
    {
        DB::table('orders')->updateOrInsert(
            ['id' => 9201010],
            [
                'supplier_id' => 920,
                'branch_id' => 92001,
                'distributor_id' => 92011,
                'customer_type' => 'b2b',
                'customer_id' => null,
                'consumer_id' => null,
                'seller_type' => 'branch',
                'seller_id' => 92001,
                'customer_name' => 'عميل تكرار أوفلاين',
                'customer_phone' => '779009936',
                'customer_address' => '15.31,44.19',
                'total_price' => 5000,
                'status' => 'out_for_delivery',
                'distributor_stage' => 'out_for_delivery',
                'created_by' => 9201,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $account = DistributorAccount::query()->findOrFail(920001);
        $this->actingAs($account, 'distributor');

        $payload = [
            'events' => [
                [
                    'type' => 'status_update',
                    'order_id' => 9201010,
                    'stage' => 'delivered',
                    'client_event_id' => 'dup-evt-001',
                ],
                [
                    'type' => 'status_update',
                    'order_id' => 9201010,
                    'stage' => 'delivered',
                    'client_event_id' => 'dup-evt-001',
                ],
            ],
        ];

        $this->postJson(route('distributor.orders.offline-sync'), $payload)
            ->assertOk()
            ->assertJsonPath('processed', 1)
            ->assertJsonPath('skipped', 1);
    }
}
