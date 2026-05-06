<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoLinkedOperationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $supplierId = $this->resolveSupplierId();
            $this->ensureSupplier($supplierId, $now);

            $this->ensureAgent(1, $supplierId, $now);
            $this->ensureBranch(36, $supplierId, $now);
            $this->ensureDistributor(130, $supplierId, 36, $now);
            $this->ensureBranchAccount(36, $now);
            $this->ensureDistributorAccount(130, $now);
            $this->ensurePos(2, $now);
            $this->ensureWorkshop(1, $now);
            $this->ensureConsumer(1, $now);
            $this->ensureCustomer(5001, $now);

            $this->seedCatalogAndStock($supplierId, 36, 1, $now);
            $this->seedOrdersAndFinance($supplierId, 36, 130, 1, 5001, $now);
            $this->seedPosOperations(2, 36, $now);
            $this->seedWorkshopOperations(1, 36, $now);
            $this->seedConsumerExperience(1, 2, 1, $now);
        });
    }

    private function resolveSupplierId(): int
    {
        $fromAgent = DB::table('agents')->where('id', 1)->value('supplier_id');
        if ($fromAgent) {
            return (int) $fromAgent;
        }

        $fromBranch = DB::table('branches')->where('id', 36)->value('supplier_id');
        if ($fromBranch) {
            return (int) $fromBranch;
        }

        $fromDistributor = DB::table('distributors')->where('id', 130)->value('supplier_id');
        if ($fromDistributor) {
            return (int) $fromDistributor;
        }

        $fromProducts = DB::table('products')->orderBy('id')->value('supplier_id');
        if ($fromProducts) {
            return (int) $fromProducts;
        }

        return 5001;
    }

    private function ensureSupplier(int $supplierId, Carbon $now): void
    {
        $existing = DB::table('suppliers')->where('id', $supplierId)->first();

        if ($existing) {
            DB::table('suppliers')->where('id', $supplierId)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('suppliers')->insert([
            'id' => $supplierId,
            'owner_name' => 'مالك تجريبي',
            'branch_manager_name' => 'مدير فرع تجريبي',
            'business_name' => 'مؤسسة تجريبية متكاملة',
            'commercial_reg_number' => 'CR-DEMO-' . $supplierId,
            'commercial_reg_image' => null,
            'license_number' => 'LIC-DEMO-' . $supplierId,
            'license_image' => null,
            'national_id_number' => 'NID-DEMO-' . $supplierId,
            'national_id_image' => null,
            'phone' => '779950001',
            'whatsapp' => '779950002',
            'address' => 'صنعاء - عنوان تجريبي للمورد',
            'gps_location' => '15.3694,44.1910',
            'email' => 'supplier.demo@example.com',
            'working_hours' => 'السبت - الخميس 08:00-18:00',
            'status' => 'active',
            'is_verified' => 1,
            'verified_at' => $now,
            'verified_by_user_id' => null,
            'verification_requested_at' => null,
            'verification_requested_by_user_id' => null,
            'agent_image' => null,
            'branch_manager_image' => null,
            'branch_manager_password' => Hash::make('123456'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureAgent(int $id, int $supplierId, Carbon $now): void
    {
        $existing = DB::table('agents')->where('id', $id)->first();

        if ($existing) {
            DB::table('agents')->where('id', $id)->update([
                'supplier_id' => $supplierId,
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('agents')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'name' => 'وكيل تجريبي رئيسي',
            'email' => 'agent.demo@example.com',
            'phone' => '779950101',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureBranch(int $id, int $supplierId, Carbon $now): void
    {
        $existing = DB::table('branches')->where('id', $id)->first();

        if ($existing) {
            DB::table('branches')->where('id', $id)->update([
                'supplier_id' => $supplierId,
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('branches')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'name' => 'فرع تجريبي رقم 36',
            'phone' => '779950236',
            'branch_manager_name' => 'مدير الفرع 36',
            'branch_manager_image' => null,
            'branch_manager_password' => Hash::make('123456'),
            'address' => 'صنعاء - شارع الفرع التجريبي',
            'gps_location' => '15.3600,44.2100',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureDistributor(int $id, int $supplierId, int $branchId, Carbon $now): void
    {
        $existing = DB::table('distributors')->where('id', $id)->first();

        if ($existing) {
            DB::table('distributors')->where('id', $id)->update([
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('distributors')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'branch_id' => $branchId,
            'name' => 'مندوب تجريبي رقم 130',
            'phone' => '779950130',
            'password' => Hash::make('123456'),
            'image' => null,
            'vehicle_type' => 'دراجة نارية',
            'distribution_points' => 12,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureBranchAccount(int $branchId, Carbon $now): void
    {
        $record = DB::table('branch_accounts')->where('branch_id', $branchId)->first();

        if ($record) {
            DB::table('branch_accounts')->where('id', $record->id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('branch_accounts')->insert([
            'branch_id' => $branchId,
            'name' => 'حساب فرع 36',
            'phone' => '779950336',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureDistributorAccount(int $distributorId, Carbon $now): void
    {
        $record = DB::table('distributor_accounts')->where('distributor_id', $distributorId)->first();

        if ($record) {
            DB::table('distributor_accounts')->where('id', $record->id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('distributor_accounts')->insert([
            'distributor_id' => $distributorId,
            'name' => 'حساب مندوب 130',
            'phone' => '779950430',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensurePos(int $id, Carbon $now): void
    {
        $existing = DB::table('pos_accounts')->where('id', $id)->first();

        if ($existing) {
            DB::table('pos_accounts')->where('id', $id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('pos_accounts')->insert([
            'id' => $id,
            'name' => 'محل تجاري تجريبية 2',
            'phone' => '779950502',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureWorkshop(int $id, Carbon $now): void
    {
        $existing = DB::table('workshop_accounts')->where('id', $id)->first();

        if ($existing) {
            DB::table('workshop_accounts')->where('id', $id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('workshop_accounts')->insert([
            'id' => $id,
            'name' => 'ورشة صيانة تجريبية 1',
            'phone' => '779950601',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureConsumer(int $id, Carbon $now): void
    {
        $existing = DB::table('consumers')->where('id', $id)->first();

        if ($existing) {
            DB::table('consumers')->where('id', $id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('consumers')->insert([
            'id' => $id,
            'name' => 'مستهلك تجريبي 1',
            'phone' => '779950701',
            'password' => Hash::make('123456'),
            'whatsapp' => '779950702',
            'address' => 'صنعاء - الحي السياسي',
            'gps_location' => '15.3000,44.2300',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureCustomer(int $id, Carbon $now): void
    {
        $existing = DB::table('customers')->where('id', $id)->first();

        if ($existing) {
            DB::table('customers')->where('id', $id)->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('customers')->insert([
            'id' => $id,
            'type' => 'retail_store',
            'name' => 'عميل تجاري تجريبي',
            'phone' => '779950801',
            'password' => Hash::make('123456'),
            'whatsapp' => '779950802',
            'address' => 'صنعاء - شارع التجارة',
            'gps_location' => '15.3550,44.2180',
            'owner_name' => 'صاحب المتجر',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedCatalogAndStock(int $supplierId, int $branchId, int $agentId, Carbon $now): void
    {
        DB::table('categories')->updateOrInsert(
            ['id' => 7001],
            ['name' => 'قطع غيار تجريبية', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 7001],
            ['name' => 'قطعة-تجريبي-7001', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 7002],
            ['name' => 'علبة-تجريبي-7002', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 8001],
            [
                'supplier_id' => $supplierId,
                'category_id' => 7001,
                'name' => 'فلتر زيت تجريبي',
                'model' => 'FILT-8001',
                'car_models' => json_encode([2018, 2019, 2020, 2021], JSON_UNESCAPED_UNICODE),
                'production_year_from' => 2018,
                'production_year_to' => 2021,
                'description' => 'منتج تجريبي لعرض الطلبات والمخزون',
                'image' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 8002],
            [
                'supplier_id' => $supplierId,
                'category_id' => 7001,
                'name' => 'زيت محرك تجريبي 4 لتر',
                'model' => 'OIL-8002',
                'car_models' => json_encode([2016, 2017, 2018, 2019, 2020], JSON_UNESCAPED_UNICODE),
                'production_year_from' => 2016,
                'production_year_to' => 2020,
                'description' => 'منتج تجريبي للمبيعات والمحل التجاري',
                'image' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_units')->updateOrInsert(
            ['id' => 8101],
            [
                'product_id' => 8001,
                'unit_id' => 7001,
                'wholesale_price' => 3200,
                'retail_price' => 4000,
                'conversion_factor' => 1,
                'stock_quantity' => 200,
                'low_stock_threshold' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_units')->updateOrInsert(
            ['id' => 8102],
            [
                'product_id' => 8002,
                'unit_id' => 7002,
                'wholesale_price' => 9500,
                'retail_price' => 11500,
                'conversion_factor' => 1,
                'stock_quantity' => 150,
                'low_stock_threshold' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => $branchId, 'product_unit_id' => 8101],
            [
                'product_id' => 8001,
                'quantity' => 90,
                'selling_price' => 4200,
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('branch_product_stocks')->updateOrInsert(
            ['branch_id' => $branchId, 'product_unit_id' => 8102],
            [
                'product_id' => 8002,
                'quantity' => 70,
                'selling_price' => 11800,
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('inventory_movements')->updateOrInsert(
            ['id' => 8201],
            [
                'supplier_id' => $supplierId,
                'product_id' => 8001,
                'product_unit_id' => 8101,
                'branch_id' => $branchId,
                'agent_id' => $agentId,
                'movement_type' => 'in',
                'quantity' => 30,
                'stock_before' => 60,
                'stock_after' => 90,
                'note' => 'توريد تجريبي للفرع',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ]
        );

        DB::table('inventory_movements')->updateOrInsert(
            ['id' => 8202],
            [
                'supplier_id' => $supplierId,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'branch_id' => $branchId,
                'agent_id' => $agentId,
                'movement_type' => 'in',
                'quantity' => 25,
                'stock_before' => 45,
                'stock_after' => 70,
                'note' => 'توريد زيوت تجريبي',
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ]
        );

        $stock1Id = (int) DB::table('branch_product_stocks')
            ->where('branch_id', $branchId)
            ->where('product_unit_id', 8101)
            ->value('id');

        $stock2Id = (int) DB::table('branch_product_stocks')
            ->where('branch_id', $branchId)
            ->where('product_unit_id', 8102)
            ->value('id');

        DB::table('branch_stock_movements')->updateOrInsert(
            ['id' => 8301],
            [
                'branch_id' => $branchId,
                'product_id' => 8001,
                'product_unit_id' => 8101,
                'inventory_movement_id' => 8201,
                'order_id' => null,
                'distributor_id' => null,
                'movement_type' => 'inbound',
                'quantity' => 30,
                'stock_before' => 60,
                'stock_after' => 90,
                'note' => 'دخول مخزون أولي تجريبي',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ]
        );

        DB::table('branch_stock_movements')->updateOrInsert(
            ['id' => 8302],
            [
                'branch_id' => $branchId,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'inventory_movement_id' => 8202,
                'order_id' => null,
                'distributor_id' => null,
                'movement_type' => 'inbound',
                'quantity' => 25,
                'stock_before' => 45,
                'stock_after' => 70,
                'note' => 'دخول مخزون زيت تجريبي',
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ]
        );

        DB::table('branch_replenishment_requests')->updateOrInsert(
            ['id' => 8401],
            [
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'requested_quantity' => 20,
                'status' => 'approved',
                'note' => 'طلب تعزيز مخزون تجريبي',
                'requested_at' => $now->copy()->subDays(3),
                'resolved_at' => $now->copy()->subDays(2),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(2),
            ]
        );
    }

    private function seedOrdersAndFinance(
        int $supplierId,
        int $branchId,
        int $distributorId,
        int $consumerId,
        int $customerId,
        Carbon $now
    ): void {
        DB::table('orders')->updateOrInsert(
            ['id' => 9001],
            [
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'distributor_id' => $distributorId,
                'customer_type' => 'b2c',
                'customer_id' => null,
                'consumer_id' => $consumerId,
                'seller_type' => 'branch',
                'seller_id' => $branchId,
                'customer_name' => 'مستهلك تجريبي 1',
                'customer_phone' => (string) DB::table('consumers')->where('id', $consumerId)->value('phone'),
                'customer_address' => 'صنعاء - عنوان توصيل تجريبي',
                'total_price' => 27800,
                'status' => 'delivered',
                'distributor_stage' => 'delivered',
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(1),
            ]
        );

        DB::table('orders')->updateOrInsert(
            ['id' => 9002],
            [
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'distributor_id' => null,
                'customer_type' => 'b2b',
                'customer_id' => $customerId,
                'consumer_id' => null,
                'seller_type' => 'supplier',
                'seller_id' => $supplierId,
                'customer_name' => 'عميل تجاري تجريبي',
                'customer_phone' => (string) DB::table('customers')->where('id', $customerId)->value('phone'),
                'customer_address' => 'صنعاء - شارع التجارة',
                'total_price' => 56000,
                'status' => 'approved',
                'distributor_stage' => 'assigned',
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now,
            ]
        );

        DB::table('order_items')->whereIn('order_id', [9001, 9002])->delete();

        DB::table('order_items')->insert([
            [
                'order_id' => 9001,
                'product_id' => 8001,
                'product_unit_id' => 8101,
                'product_variant_id' => null,
                'quantity' => 2,
                'unit_price' => 4200,
                'total' => 8400,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'order_id' => 9001,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => 19400,
                'total' => 19400,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'order_id' => 9002,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'product_variant_id' => null,
                'quantity' => 4,
                'unit_price' => 14000,
                'total' => 56000,
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ],
        ]);

        DB::table('payments')->updateOrInsert(
            ['id' => 9101],
            [
                'order_id' => 9001,
                'supplier_id' => $supplierId,
                'distributor_id' => $distributorId,
                'amount' => 27800,
                'payment_type' => 'cash',
                'status' => 'paid',
                'paid_at' => $now->copy()->subDay(),
                'notes' => 'تحصيل نقدي من المستهلك',
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ]
        );

        DB::table('payments')->updateOrInsert(
            ['id' => 9102],
            [
                'order_id' => 9002,
                'supplier_id' => $supplierId,
                'distributor_id' => null,
                'amount' => 30000,
                'payment_type' => 'credit',
                'status' => 'partial',
                'paid_at' => $now,
                'notes' => 'دفعة جزئية للعميل التجاري',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('distributor_order_events')->where('order_id', 9001)->delete();
        DB::table('distributor_order_events')->insert([
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'stage' => 'assigned',
                'note' => 'تم إسناد الطلب للمندوب',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'stage' => 'picked_up',
                'note' => 'تم استلام الطلب من الفرع',
                'created_at' => $now->copy()->subDays(2)->addHours(1),
                'updated_at' => $now->copy()->subDays(2)->addHours(1),
            ],
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'stage' => 'on_way',
                'note' => 'المندوب في الطريق',
                'created_at' => $now->copy()->subDays(2)->addHours(2),
                'updated_at' => $now->copy()->subDays(2)->addHours(2),
            ],
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'stage' => 'delivered',
                'note' => 'تم التسليم بنجاح',
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ],
        ]);

        DB::table('distributor_location_logs')->where('order_id', 9001)->delete();
        DB::table('distributor_location_logs')->insert([
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'latitude' => 15.3610123,
                'longitude' => 44.2089123,
                'accuracy_meters' => 12.5,
                'note' => 'بداية المسار',
                'created_at' => $now->copy()->subDays(2)->addMinutes(20),
                'updated_at' => $now->copy()->subDays(2)->addMinutes(20),
            ],
            [
                'distributor_id' => $distributorId,
                'order_id' => 9001,
                'latitude' => 15.3491123,
                'longitude' => 44.2191123,
                'accuracy_meters' => 9.8,
                'note' => 'قرب موقع العميل',
                'created_at' => $now->copy()->subDays(2)->addMinutes(45),
                'updated_at' => $now->copy()->subDays(2)->addMinutes(45),
            ],
        ]);

        DB::table('branch_stock_movements')->updateOrInsert(
            ['id' => 9301],
            [
                'branch_id' => $branchId,
                'product_id' => 8001,
                'product_unit_id' => 8101,
                'inventory_movement_id' => null,
                'order_id' => 9001,
                'distributor_id' => $distributorId,
                'movement_type' => 'sale',
                'quantity' => 2,
                'stock_before' => 92,
                'stock_after' => 90,
                'note' => 'خصم بيع بسبب الطلب 9001',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ]
        );

        DB::table('branch_stock_movements')->updateOrInsert(
            ['id' => 9302],
            [
                'branch_id' => $branchId,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'inventory_movement_id' => null,
                'order_id' => 9001,
                'distributor_id' => $distributorId,
                'movement_type' => 'sale',
                'quantity' => 1,
                'stock_before' => 71,
                'stock_after' => 70,
                'note' => 'خصم بيع زيت بسبب الطلب 9001',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ]
        );
    }

    private function seedPosOperations(int $posId, int $branchId, Carbon $now): void
    {
        DB::table('pos_local_products')->updateOrInsert(
            ['id' => 9401],
            [
                'pos_account_id' => $posId,
                'branch_id' => $branchId,
                'product_id' => 8001,
                'product_unit_id' => 8101,
                'purchase_price' => 3200,
                'selling_price' => 4300,
                'local_quantity' => 25,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now,
            ]
        );

        DB::table('pos_local_products')->updateOrInsert(
            ['id' => 9402],
            [
                'pos_account_id' => $posId,
                'branch_id' => $branchId,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'purchase_price' => 9500,
                'selling_price' => 12000,
                'local_quantity' => 18,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now,
            ]
        );

        DB::table('pos_sales')->updateOrInsert(
            ['id' => 9501],
            [
                'pos_account_id' => $posId,
                'pos_local_product_id' => 9401,
                'order_id' => null,
                'product_name' => 'فلتر زيت تجريبي',
                'customer_name' => 'عميل مباشر',
                'customer_phone' => '779955500',
                'sale_channel' => 'offline',
                'quantity' => 1,
                'unit_price' => 4300,
                'total_amount' => 4300,
                'profit_amount' => 1100,
                'note' => 'بيع نقدي من المحل التجاري',
                'sold_at' => $now->copy()->subHours(6),
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(6),
            ]
        );

        DB::table('pos_sales')->updateOrInsert(
            ['id' => 9502],
            [
                'pos_account_id' => $posId,
                'pos_local_product_id' => 9402,
                'order_id' => 9001,
                'product_name' => 'زيت محرك تجريبي 4 لتر',
                'customer_name' => 'مستهلك تجريبي 1',
                'customer_phone' => (string) DB::table('consumers')->where('id', 1)->value('phone'),
                'sale_channel' => 'online',
                'quantity' => 1,
                'unit_price' => 12000,
                'total_amount' => 12000,
                'profit_amount' => 2500,
                'note' => 'بيع مرتبط بطلب أونلاين 9001',
                'sold_at' => $now->copy()->subDay(),
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ]
        );
    }

    private function seedWorkshopOperations(int $workshopId, int $branchId, Carbon $now): void
    {
        DB::table('workshop_services')->updateOrInsert(
            ['id' => 9601],
            [
                'workshop_id' => $workshopId,
                'name' => 'تغيير زيت سريع',
                'description' => 'خدمة صيانة دورية تجريبية',
                'price' => 7000,
                'duration_minutes' => 35,
                'requires_products' => 1,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_services')->updateOrInsert(
            ['id' => 9602],
            [
                'workshop_id' => $workshopId,
                'name' => 'فحص وفرامل',
                'description' => 'فحص شامل لمنظومة الفرامل',
                'price' => 5500,
                'duration_minutes' => 25,
                'requires_products' => 0,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now,
            ]
        );

        DB::table('workshop_appointments')->updateOrInsert(
            ['id' => 9701],
            [
                'workshop_id' => $workshopId,
                'service_id' => 9601,
                'customer_name' => 'مستهلك تجريبي 1',
                'customer_phone' => (string) DB::table('consumers')->where('id', 1)->value('phone'),
                'vehicle_details' => 'Toyota Corolla 2019',
                'appointment_at' => $now->copy()->subHours(8),
                'estimated_minutes' => 40,
                'status' => 'completed',
                'notes' => 'حجز عبر التطبيق',
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subHours(7),
            ]
        );

        DB::table('workshop_service_orders')->updateOrInsert(
            ['id' => 9801],
            [
                'workshop_id' => $workshopId,
                'service_id' => 9601,
                'appointment_id' => 9701,
                'order_number' => 'WSO-DEMO-9801',
                'customer_name' => 'مستهلك تجريبي 1',
                'customer_phone' => (string) DB::table('consumers')->where('id', 1)->value('phone'),
                'service_fee' => 7000,
                'products_total' => 4500,
                'total_amount' => 11500,
                'status' => 'completed',
                'notes' => 'خدمة مكتملة مع استهلاك منتج',
                'used_products' => json_encode([
                    ['product_id' => 8002, 'product_unit_id' => 8102, 'quantity' => 1],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now->copy()->subHours(7),
                'updated_at' => $now->copy()->subHours(6),
            ]
        );

        DB::table('workshop_purchase_orders')->updateOrInsert(
            ['id' => 9901],
            [
                'workshop_id' => $workshopId,
                'supplier_branch_id' => $branchId,
                'order_number' => 'WPO-DEMO-9901',
                'supplier_branch_name' => (string) DB::table('branches')->where('id', $branchId)->value('name'),
                'total_amount' => 23600,
                'status' => 'received',
                'stock_deducted_at' => $now->copy()->subDays(1),
                'stock_restored_at' => null,
                'notes' => 'طلب شراء منتجات للورشة',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDay(),
            ]
        );

        $branchStockId = (int) DB::table('branch_product_stocks')
            ->where('branch_id', $branchId)
            ->where('product_unit_id', 8102)
            ->value('id');

        DB::table('workshop_purchase_order_items')->updateOrInsert(
            ['id' => 9911],
            [
                'purchase_order_id' => 9901,
                'branch_product_stock_id' => $branchStockId > 0 ? $branchStockId : null,
                'product_id' => 8002,
                'product_unit_id' => 8102,
                'quantity' => 2,
                'unit_price' => 11800,
                'line_total' => 23600,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDay(),
            ]
        );
    }

    private function seedConsumerExperience(int $consumerId, int $posId, int $workshopId, Carbon $now): void
    {
        DB::table('consumer_addresses')->where('consumer_id', $consumerId)->delete();
        DB::table('consumer_addresses')->insert([
            [
                'consumer_id' => $consumerId,
                'label' => 'المنزل',
                'contact_name' => 'مستهلك تجريبي 1',
                'phone' => (string) DB::table('consumers')->where('id', $consumerId)->value('phone'),
                'address_line' => 'صنعاء - الحي السياسي - شارع 45',
                'gps_location' => '15.3001,44.2302',
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'consumer_id' => $consumerId,
                'label' => 'العمل',
                'contact_name' => 'مستهلك تجريبي 1',
                'phone' => (string) DB::table('consumers')->where('id', $consumerId)->value('phone'),
                'address_line' => 'صنعاء - جولة المصباحي',
                'gps_location' => '15.3120,44.2210',
                'is_default' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('consumer_ratings')->updateOrInsert(
            ['id' => 9951],
            [
                'consumer_id' => $consumerId,
                'store_type' => 'pos',
                'store_id' => $posId,
                'order_id' => 9001,
                'rating' => 5,
                'review' => 'تجربة ممتازة وسرعة في التسليم.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('consumer_ratings')->updateOrInsert(
            ['id' => 9952],
            [
                'consumer_id' => $consumerId,
                'store_type' => 'workshop',
                'store_id' => $workshopId,
                'order_id' => null,
                'rating' => 4,
                'review' => 'الخدمة جيدة والوقت مناسب.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
