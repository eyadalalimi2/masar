<?php

namespace Tests\Feature;

use App\Services\Security\PortalPermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAccountPermissionCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Portal permission command tests require MySQL in this project environment.');
        }

        $now = now();

        DB::table('suppliers')->updateOrInsert(
            ['id' => 990],
            [
                'owner_name' => 'Owner 990',
                'business_name' => 'Supplier 990',
                'commercial_reg_number' => 'CR-990',
                'commercial_reg_image' => null,
                'license_number' => 'LIC-990',
                'license_image' => null,
                'national_id_number' => 'NID-990',
                'national_id_image' => null,
                'phone' => '779990100',
                'whatsapp' => '779990101',
                'address' => 'Supplier Address 990',
                'gps_location' => '15.30,44.20',
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
            ['id' => 99011],
            [
                'supplier_id' => 990,
                'name' => 'Agent 99011',
                'email' => 'agent99011@example.test',
                'phone' => '779990111',
                'password' => Hash::make('123456'),
                'status' => 'active',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function test_command_grants_denies_and_revokes_explicit_permission(): void
    {
        config()->set('operations.security.use_default_grants_fallback', false);

        $this->artisan('permissions:portal-account', [
            'guard' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
        ])->assertSuccessful();

        $this->assertDatabaseHas('portal_account_permissions', [
            'guard_name' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
            'is_granted' => 1,
        ]);

        $service = app(PortalPermissionService::class);
        $actor = (object) ['id' => 99011];
        $this->assertTrue($service->hasPermission('agent', $actor, 'orders.manage'));

        $this->artisan('permissions:portal-account', [
            'guard' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
            '--deny' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('portal_account_permissions', [
            'guard_name' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
            'is_granted' => 0,
        ]);

        $this->assertFalse($service->hasPermission('agent', $actor, 'orders.manage'));

        $this->artisan('permissions:portal-account', [
            'guard' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
            '--revoke' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('portal_account_permissions', [
            'guard_name' => 'agent',
            'account_id' => 99011,
            'permission' => 'orders.manage',
        ]);

        $this->assertFalse($service->hasPermission('agent', $actor, 'orders.manage'));
    }

    public function test_command_rejects_grant_for_missing_account(): void
    {
        $this->artisan('permissions:portal-account', [
            'guard' => 'agent',
            'account_id' => 999999,
            'permission' => 'orders.manage',
        ])
            ->expectsOutput('Account not found for guard [agent] and id [999999].')
            ->assertFailed();
    }
}
