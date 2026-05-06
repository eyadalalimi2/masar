<?php

namespace App\Exports\Admin;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountOpeningTemplateExport implements FromArray, WithHeadings
{
    public function __construct(private readonly string $type) {}

    public function headings(): array
    {
        return match ($this->type) {
            'supplier' => $this->supplierHeadings(),
            'commercial_store', 'workshop' => $this->customerHeadings(),
            default => [],
        };
    }

    public function array(): array
    {
        return match ($this->type) {
            'supplier' => [$this->supplierSampleRow()],
            'commercial_store' => [$this->customerSampleRow('retail_store')],
            'workshop' => [$this->customerSampleRow('workshop')],
            default => [],
        };
    }

    private function supplierHeadings(): array
    {
        return array_merge([
            'owner_name',
            'business_name',
            'phone',
            'password',
            'email',
            'whatsapp',
            'address',
            'gps_location',
            'status',
            'national_id_number',
            'commercial_reg_number',
            'license_number',
            'branch_manager_name',
            'branch_manager_password',
        ], $this->workingHoursHeadings());
    }

    private function customerHeadings(): array
    {
        return array_merge([
            'name',
            'phone',
            'password',
            'whatsapp',
            'address',
            'gps_location',
            'status',
            'owner_name',
            'national_id_number',
            'commercial_reg_number',
            'license_number',
        ], $this->workingHoursHeadings());
    }

    private function supplierSampleRow(): array
    {
        $row = [
            'owner_name' => 'مالك تجريبي',
            'business_name' => 'وكالة الغد',
            'phone' => '770000001',
            'password' => '123456',
            'email' => 'agent@example.com',
            'whatsapp' => '770000001',
            'address' => 'صنعاء - التحرير',
            'gps_location' => '15.369445,44.191006',
            'status' => 'active',
            'national_id_number' => 'NID-001',
            'commercial_reg_number' => 'CR-001',
            'license_number' => 'LIC-001',
            'branch_manager_name' => 'مدير الفرع',
            'branch_manager_password' => '123456',
        ];

        return array_merge($row, $this->sampleWorkingHours());
    }

    private function customerSampleRow(string $type): array
    {
        $name = $type === 'retail_store' ? 'محل تجاري تجريبي' : 'ورشة تجريبية';

        $row = [
            'name' => $name,
            'phone' => '770000101',
            'password' => '123456',
            'whatsapp' => '770000101',
            'address' => 'صنعاء - شارع الزبيري',
            'gps_location' => '15.360000,44.200000',
            'status' => 'active',
            'owner_name' => 'صاحب النشاط',
            'national_id_number' => 'NID-101',
            'commercial_reg_number' => 'CR-101',
            'license_number' => 'LIC-101',
        ];

        return array_merge($row, $this->sampleWorkingHours());
    }

    private function workingHoursHeadings(): array
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $headings = [];

        foreach ($days as $day) {
            $headings[] = $day . '_enabled';
            $headings[] = $day . '_start';
            $headings[] = $day . '_end';
        }

        return $headings;
    }

    private function sampleWorkingHours(): array
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $row = [];

        foreach ($days as $day) {
            $row[$day . '_enabled'] = Arr::first(['friday']) === $day ? 0 : 1;
            $row[$day . '_start'] = $day === 'friday' ? '' : '08:00';
            $row[$day . '_end'] = $day === 'friday' ? '' : '17:00';
        }

        return $row;
    }
}
