<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Customer\Consumer;
use App\Models\Customer\Customer;
use App\Models\Customer\Workshop;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorAccount;
use App\Models\Pos;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['phone' => '770450001'],
            [
                'name' => 'م. خالد العنسي',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        $supplier = Supplier::updateOrCreate(
            ['phone' => '770450101'],
            [
                'owner_name' => 'أحمد محمد الصلاحي',
                'business_name' => 'مؤسسة الصلاحي لزيوت السيارات',
                'commercial_reg_number' => 'CR-2026-4501',
                'license_number' => 'LIC-2026-9981',
                'national_id_number' => 'NID-45010111',
                'phone' => '770450101',
                'whatsapp' => '770450102',
                'address' => 'صنعاء - شارع تعز - جوار جولة المصباحي',
                'gps_location' => '15.3350,44.1900',
                'email' => 'ahmed.salahi@example.com',
                'working_hours' => 'السبت - الخميس 8:00 ص إلى 8:00 م',
                'status' => 'active',
            ]
        );

        Agent::updateOrCreate(
            ['phone' => '770450101'],
            [
                'supplier_id' => $supplier->id,
                'name' => 'أحمد محمد الصلاحي',
                'email' => 'ahmed.salahi@example.com',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        Branch::updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'phone' => '770027718',
            ],
            [
                'name' => 'فرع التحرير',
                'address' => 'صنعاء - التحرير - شارع الزبيري',
                'gps_location' => '15.3522,44.2060',
                'status' => 'active',
            ]
        );

        $branch = Branch::query()
            ->where('supplier_id', $supplier->id)
            ->where('phone', '770027718')
            ->firstOrFail();

        BranchAccount::updateOrCreate(
            ['phone' => '770027718'],
            [
                'branch_id' => $branch->id,
                'name' => 'فرع التحرير',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        Distributor::updateOrCreate(
            ['phone' => '770450301'],
            [
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'name' => 'سامي فؤاد القيسي',
                'phone' => '770450301',
                'password' => Hash::make('123456'),
                'vehicle_type' => 'دراجة نارية',
                'distribution_points' => "التحرير\nشعوب\nالسنينة",
                'status' => 'active',
            ]
        );

        $distributor = Distributor::query()->where('phone', '770450301')->firstOrFail();

        DistributorAccount::updateOrCreate(
            ['phone' => '770450301'],
            [
                'distributor_id' => $distributor->id,
                'name' => 'سامي فؤاد القيسي',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        Customer::updateOrCreate(
            ['phone' => '770450401'],
            [
                'type' => 'workshop',
                'name' => 'ورشة النخبة لصيانة السيارات',
                'phone' => '770450401',
                'password' => Hash::make('123456'),
                'whatsapp' => '770450402',
                'address' => 'صنعاء - حدة - شارع الجزائر',
                'gps_location' => '15.3318,44.2111',
                'owner_name' => 'صالح عبدالكريم الجندي',
                'status' => 'active',
            ]
        );

        Workshop::updateOrCreate(
            ['phone' => '770450401'],
            [
                'name' => 'ورشة النخبة لصيانة السيارات',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        Pos::updateOrCreate(
            ['phone' => '770450601'],
            [
                'name' => 'محل تجاري النخبة',
                'password' => Hash::make('123456'),
                'status' => 'active',
            ]
        );

        Consumer::updateOrCreate(
            ['phone' => '770450501'],
            [
                'name' => 'مازن علي الشميري',
                'phone' => '770450501',
                'password' => Hash::make('123456'),
                'whatsapp' => '770450502',
                'address' => 'صنعاء - السبعين - قرب دار سلم',
                'gps_location' => '15.2820,44.2190',
                'status' => 'active',
            ]
        );
    }
}
