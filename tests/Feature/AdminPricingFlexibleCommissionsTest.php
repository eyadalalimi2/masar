<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPricingFlexibleCommissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Admin pricing tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('admins')->updateOrInsert(
            ['id' => 968001],
            [
                'name' => 'Admin 968001',
                'phone' => '779968001',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_admin_can_store_flexible_commission_rule_with_scope_fields(): void
    {
        $admin = Admin::query()->findOrFail(968001);
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.pricing.rules.store'), [
            'name' => 'Supplier Sanaa Rule',
            'entity_type' => 'supplier',
            'entity_id' => 9651,
            'region_key' => 'Sanaa',
            'commission_percent' => 4.5,
            'service_fee' => 2,
            'fixed_fee' => 1,
            'priority' => 10,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_commission_rules', [
            'name' => 'Supplier Sanaa Rule',
            'entity_type' => 'supplier',
            'entity_id' => 9651,
            'region_key' => 'sanaa',
            'priority' => 10,
            'is_active' => 1,
        ]);
    }

    public function test_commission_preview_uses_more_specific_rule(): void
    {
        $admin = Admin::query()->findOrFail(968001);
        $this->actingAs($admin, 'admin');

        DB::table('admin_commission_rules')->insert([
            [
                'name' => 'Global Base Rule',
                'entity_type' => 'global',
                'entity_id' => null,
                'region_key' => null,
                'commission_percent' => 2,
                'service_fee' => 1,
                'fixed_fee' => 0,
                'priority' => 100,
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Supplier Specific Rule',
                'entity_type' => 'supplier',
                'entity_id' => 9651,
                'region_key' => 'sanaa',
                'commission_percent' => 5,
                'service_fee' => 3,
                'fixed_fee' => 2,
                'priority' => 5,
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->postJson(route('admin.pricing.rules.preview'), [
            'base_amount' => 100,
            'entity_type' => 'supplier',
            'entity_id' => 9651,
            'region_key' => 'Sanaa',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.rule_name', 'Supplier Specific Rule')
            ->assertJsonPath('result.commission_value', 5.0)
            ->assertJsonPath('result.final_amount', 110.0);
    }
}
