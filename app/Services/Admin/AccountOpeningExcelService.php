<?php

namespace App\Services\Admin;

use App\Exports\Admin\AccountOpeningDataExport;
use App\Imports\RawArrayImport;
use App\Models\Customer\Customer;
use App\Models\Finance\Account;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use App\Services\Customer\CustomerService;
use App\Services\Supplier\SupplierService;
use App\Support\WorkingHoursCodec;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AccountOpeningExcelService
{
    private const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function __construct(
        private readonly SupplierService $supplierService,
        private readonly CustomerService $customerService,
    ) {}

    public function preview(UploadedFile $file, string $type, bool $hasHeader): array
    {
        $import = new RawArrayImport();
        $sheets = Excel::toArray($import, $file);
        $rows = $sheets[0] ?? [];

        if ($rows === []) {
            return [
                'type' => $type,
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                    'creatable_rows' => 0,
                    'updatable_rows' => 0,
                ],
            ];
        }

        $header = [];
        $startAt = 0;

        if ($hasHeader) {
            $header = $this->normalizeHeaderRow(array_shift($rows) ?? []);
            $startAt = 1;
        }

        $results = [];
        $validRows = 0;
        $creatableRows = 0;
        $updatableRows = 0;

        foreach ($rows as $index => $row) {
            $line = $index + $startAt + 1;
            $assoc = $this->toAssocRow($row, $header, $type, $hasHeader);
            $normalized = $this->normalizeRow($assoc, $type);

            $existing = $this->findExisting($normalized['phone'], $type);
            $action = $existing ? 'update' : 'create';
            $errors = $this->validateNormalizedRow($normalized, $type, $action);

            if ($errors === []) {
                $validRows++;
                if ($action === 'create') {
                    $creatableRows++;
                } else {
                    $updatableRows++;
                }
            }

            $results[] = [
                'line' => $line,
                'action' => $action,
                'errors' => $errors,
                'normalized' => $normalized,
                'display' => $this->displayRow($normalized, $type),
            ];
        }

        return [
            'type' => $type,
            'rows' => $results,
            'summary' => [
                'total_rows' => count($results),
                'valid_rows' => $validRows,
                'invalid_rows' => count($results) - $validRows,
                'creatable_rows' => $creatableRows,
                'updatable_rows' => $updatableRows,
            ],
        ];
    }

    public function importFromPreview(array $preview): array
    {
        $type = (string) ($preview['type'] ?? '');
        $rows = is_array($preview['rows'] ?? null) ? $preview['rows'] : [];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $details = [];

        foreach ($rows as $row) {
            $line = (int) ($row['line'] ?? 0);
            $action = (string) ($row['action'] ?? 'create');
            $errors = is_array($row['errors'] ?? null) ? $row['errors'] : [];
            $normalized = is_array($row['normalized'] ?? null) ? $row['normalized'] : [];

            if ($errors !== []) {
                $failed++;
                $details[] = [
                    'line' => $line,
                    'status' => 'failed',
                    'message' => implode(' | ', $errors),
                    'name' => (string) ($normalized['name'] ?? $normalized['business_name'] ?? ''),
                    'phone' => (string) ($normalized['phone'] ?? ''),
                ];
                continue;
            }

            try {
                $this->applyImportRow($type, $normalized, $action);

                if ($action === 'create') {
                    $created++;
                } else {
                    $updated++;
                }

                $details[] = [
                    'line' => $line,
                    'status' => 'success',
                    'message' => $action === 'create' ? 'تم إنشاء الحساب بنجاح.' : 'تم تحديث الحساب بنجاح.',
                    'name' => (string) ($normalized['name'] ?? $normalized['business_name'] ?? ''),
                    'phone' => (string) ($normalized['phone'] ?? ''),
                ];
            } catch (\Throwable $exception) {
                $failed++;
                $details[] = [
                    'line' => $line,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                    'name' => (string) ($normalized['name'] ?? $normalized['business_name'] ?? ''),
                    'phone' => (string) ($normalized['phone'] ?? ''),
                ];
            }
        }

        return [
            'type' => $type,
            'summary' => [
                'total_rows' => count($rows),
                'created_rows' => $created,
                'updated_rows' => $updated,
                'failed_rows' => $failed,
            ],
            'details' => $details,
        ];
    }

    public function buildExportForType(string $type): AccountOpeningDataExport
    {
        if ($type === 'supplier') {
            return $this->buildSupplierExport();
        }

        return $this->buildCustomerExport($type);
    }

    private function buildSupplierExport(): AccountOpeningDataExport
    {
        $headings = [
            'owner_name',
            'business_name',
            'phone',
            'email',
            'whatsapp',
            'address',
            'gps_location',
            'status',
            'national_id_number',
            'commercial_reg_number',
            'license_number',
            'branch_manager_name',
        ];

        foreach (self::DAYS as $day) {
            $headings[] = $day . '_enabled';
            $headings[] = $day . '_start';
            $headings[] = $day . '_end';
        }

        $rows = Supplier::query()->with('agentAccount')->latest()->get()->map(function (Supplier $supplier) {
            $hours = WorkingHoursCodec::decode($supplier->working_hours);
            $base = [
                $supplier->owner_name,
                $supplier->business_name,
                (string) optional($supplier->agentAccount)->phone,
                (string) optional($supplier->agentAccount)->email,
                $supplier->whatsapp,
                $supplier->address,
                $supplier->gps_location,
                $supplier->status,
                $supplier->national_id_number,
                $supplier->commercial_reg_number,
                $supplier->license_number,
                $supplier->branch_manager_name,
            ];

            foreach (self::DAYS as $day) {
                $dayData = $hours[$day] ?? ['enabled' => false, 'start' => null, 'end' => null];
                $base[] = (int) ((bool) ($dayData['enabled'] ?? false));
                $base[] = (string) ($dayData['start'] ?? '');
                $base[] = (string) ($dayData['end'] ?? '');
            }

            return $base;
        })->all();

        return new AccountOpeningDataExport($headings, $rows);
    }

    private function buildCustomerExport(string $type): AccountOpeningDataExport
    {
        $customerType = $type === 'workshop' ? 'workshop' : 'retail_store';

        $headings = [
            'name',
            'phone',
            'whatsapp',
            'address',
            'gps_location',
            'status',
            'owner_name',
            'national_id_number',
            'commercial_reg_number',
            'license_number',
        ];

        foreach (self::DAYS as $day) {
            $headings[] = $day . '_enabled';
            $headings[] = $day . '_start';
            $headings[] = $day . '_end';
        }

        $rows = Customer::query()
            ->where('type', $customerType)
            ->latest()
            ->get()
            ->map(function (Customer $customer) {
                $hours = WorkingHoursCodec::decode($customer->working_hours);
                $base = [
                    $customer->name,
                    $customer->phone,
                    $customer->whatsapp,
                    $customer->address,
                    $customer->gps_location,
                    $customer->status,
                    $customer->owner_name,
                    $customer->national_id_number,
                    $customer->commercial_reg_number,
                    $customer->license_number,
                ];

                foreach (self::DAYS as $day) {
                    $dayData = $hours[$day] ?? ['enabled' => false, 'start' => null, 'end' => null];
                    $base[] = (int) ((bool) ($dayData['enabled'] ?? false));
                    $base[] = (string) ($dayData['start'] ?? '');
                    $base[] = (string) ($dayData['end'] ?? '');
                }

                return $base;
            })->all();

        return new AccountOpeningDataExport($headings, $rows);
    }

    private function applyImportRow(string $type, array $row, string $action): void
    {
        if ($type === 'supplier') {
            $this->applySupplierRow($row, $action);

            return;
        }

        $this->applyCustomerRow($row, $type, $action);
    }

    private function applySupplierRow(array $row, string $action): void
    {
        $payload = [
            'owner_name' => $row['owner_name'],
            'business_name' => $row['business_name'],
            'phone' => $row['phone'],
            'password' => $row['password'] ?? null,
            'email' => $row['email'] ?: null,
            'whatsapp' => $row['whatsapp'],
            'address' => $row['address'],
            'gps_location' => $row['gps_location'],
            'status' => $row['status'],
            'national_id_number' => $row['national_id_number'],
            'commercial_reg_number' => $row['commercial_reg_number'],
            'license_number' => $row['license_number'],
            'branch_manager_name' => $row['branch_manager_name'] ?: null,
            'branch_manager_password' => $row['branch_manager_password'] ?: null,
            'working_hours' => $row['working_hours'],
        ];

        $agent = Agent::query()->where('phone', $row['phone'])->first();

        if ($action === 'update' && $agent && (int) $agent->supplier_id > 0) {
            if (! is_string($payload['password']) || trim($payload['password']) === '') {
                unset($payload['password']);
            }

            $payload['supplier_id'] = (int) $agent->supplier_id;
            $this->supplierService->updateSupplier($payload);

            return;
        }

        if (! is_string($payload['password']) || trim($payload['password']) === '') {
            throw new \DomainException('كلمة المرور مطلوبة عند إنشاء وكيل جديد.');
        }

        $this->supplierService->createSupplier($payload);
    }

    private function applyCustomerRow(array $row, string $type, string $action): void
    {
        $customerType = $type === 'workshop' ? 'workshop' : 'retail_store';

        $payload = [
            'type' => $customerType,
            'name' => $row['name'],
            'phone' => $row['phone'],
            'password' => $row['password'] ?? null,
            'whatsapp' => $row['whatsapp'] ?: null,
            'address' => $row['address'],
            'gps_location' => $row['gps_location'] ?: null,
            'status' => $row['status'],
            'owner_name' => $row['owner_name'] ?: null,
            'national_id_number' => $row['national_id_number'] ?: null,
            'commercial_reg_number' => $row['commercial_reg_number'] ?: null,
            'license_number' => $row['license_number'] ?: null,
            'working_hours' => WorkingHoursCodec::encode($row['working_hours']),
        ];

        $customer = Customer::query()
            ->where('type', $customerType)
            ->where('phone', $row['phone'])
            ->first();

        if ($action === 'update' && $customer) {
            if (! is_string($payload['password']) || trim($payload['password']) === '') {
                unset($payload['password']);
            }

            $this->customerService->update($customer, $payload);

            return;
        }

        if (! is_string($payload['password']) || trim($payload['password']) === '') {
            throw new \DomainException('كلمة المرور مطلوبة عند إنشاء حساب جديد.');
        }

        $this->customerService->create($payload);
    }

    private function validateNormalizedRow(array $row, string $type, string $action): array
    {
        $errors = [];

        if ($type === 'supplier') {
            $errors = array_merge($errors, $this->requiredFields($row, [
                'owner_name' => 'اسم المالك',
                'business_name' => 'الاسم التجاري',
                'phone' => 'الهاتف',
                'whatsapp' => 'الواتساب',
                'address' => 'العنوان',
                'gps_location' => 'الموقع',
                'national_id_number' => 'رقم البطاقة الشخصية',
                'commercial_reg_number' => 'رقم السجل التجاري',
                'license_number' => 'رقم الرخصة',
            ]));
        } else {
            $errors = array_merge($errors, $this->requiredFields($row, [
                'name' => 'الاسم',
                'phone' => 'الهاتف',
                'address' => 'العنوان',
            ]));
        }

        if ($action === 'create' && ! is_string($row['password'] ?? null)) {
            $errors[] = 'كلمة المرور مطلوبة عند الإنشاء.';
        }

        if (is_string($row['password'] ?? null) && trim((string) $row['password']) !== '' && mb_strlen((string) $row['password']) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        }

        $status = (string) ($row['status'] ?? Account::STATUS_ACTIVE);
        if (! in_array($status, Account::STATUSES, true)) {
            $errors[] = 'الحالة يجب أن تكون active أو inactive.';
        }

        $location = (string) ($row['gps_location'] ?? '');
        if ($location !== '' && ! preg_match('/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/', $location)) {
            $errors[] = 'صيغة الموقع يجب أن تكون latitude,longitude.';
        }

        $email = (string) ($row['email'] ?? '');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
        }

        return $errors;
    }

    private function requiredFields(array $row, array $fields): array
    {
        $errors = [];

        foreach ($fields as $key => $label) {
            $value = $row[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                $errors[] = 'الحقل ' . $label . ' مطلوب.';
            }
        }

        return $errors;
    }

    private function normalizeRow(array $row, string $type): array
    {
        if ($type === 'supplier') {
            return [
                'owner_name' => $this->text($row['owner_name'] ?? null),
                'business_name' => $this->text($row['business_name'] ?? null),
                'phone' => $this->text($row['phone'] ?? null),
                'password' => $this->nullableText($row['password'] ?? null),
                'email' => $this->nullableText($row['email'] ?? null),
                'whatsapp' => $this->text($row['whatsapp'] ?? null),
                'address' => $this->text($row['address'] ?? null),
                'gps_location' => $this->normalizeGps($row['gps_location'] ?? null),
                'status' => $this->status($row['status'] ?? null),
                'national_id_number' => $this->text($row['national_id_number'] ?? null),
                'commercial_reg_number' => $this->text($row['commercial_reg_number'] ?? null),
                'license_number' => $this->text($row['license_number'] ?? null),
                'branch_manager_name' => $this->nullableText($row['branch_manager_name'] ?? null),
                'branch_manager_password' => $this->nullableText($row['branch_manager_password'] ?? null),
                'working_hours' => $this->normalizeWorkingHours($row),
            ];
        }

        return [
            'name' => $this->text($row['name'] ?? null),
            'phone' => $this->text($row['phone'] ?? null),
            'password' => $this->nullableText($row['password'] ?? null),
            'whatsapp' => $this->nullableText($row['whatsapp'] ?? null),
            'address' => $this->text($row['address'] ?? null),
            'gps_location' => $this->normalizeGps($row['gps_location'] ?? null),
            'status' => $this->status($row['status'] ?? null),
            'owner_name' => $this->nullableText($row['owner_name'] ?? null),
            'national_id_number' => $this->nullableText($row['national_id_number'] ?? null),
            'commercial_reg_number' => $this->nullableText($row['commercial_reg_number'] ?? null),
            'license_number' => $this->nullableText($row['license_number'] ?? null),
            'working_hours' => $this->normalizeWorkingHours($row),
        ];
    }

    private function displayRow(array $row, string $type): array
    {
        if ($type === 'supplier') {
            return [
                'name' => (string) ($row['business_name'] ?? ''),
                'owner_name' => (string) ($row['owner_name'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return [
            'name' => (string) ($row['name'] ?? ''),
            'owner_name' => (string) ($row['owner_name'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
        ];
    }

    private function findExisting(string $phone, string $type): bool
    {
        if ($phone === '') {
            return false;
        }

        if ($type === 'supplier') {
            return Agent::query()->where('phone', $phone)->exists();
        }

        $customerType = $type === 'workshop' ? 'workshop' : 'retail_store';

        return Customer::query()
            ->where('type', $customerType)
            ->where('phone', $phone)
            ->exists();
    }

    private function toAssocRow(array $row, array $header, string $type, bool $hasHeader): array
    {
        if ($hasHeader && $header !== []) {
            $assoc = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$index] ?? null;
            }

            return $assoc;
        }

        $keys = $type === 'supplier'
            ? [
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
            ]
            : [
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
            ];

        foreach (self::DAYS as $day) {
            $keys[] = $day . '_enabled';
            $keys[] = $day . '_start';
            $keys[] = $day . '_end';
        }

        $assoc = [];
        foreach ($keys as $index => $key) {
            $assoc[$key] = $row[$index] ?? null;
        }

        return $assoc;
    }

    private function normalizeHeaderRow(array $row): array
    {
        return array_map(function ($value) {
            $normalized = Str::of((string) $value)
                ->trim()
                ->lower()
                ->replace(' ', '_')
                ->replace('-', '_')
                ->toString();

            return preg_replace('/[^a-z0-9_]/', '', $normalized) ?? '';
        }, $row);
    }

    private function normalizeWorkingHours(array $row): array
    {
        $schedule = [];
        $hasExplicitHours = false;

        foreach (self::DAYS as $day) {
            $enabledRaw = $row[$day . '_enabled'] ?? null;
            $startRaw = $row[$day . '_start'] ?? null;
            $endRaw = $row[$day . '_end'] ?? null;

            if (
                $this->nullableText($enabledRaw) !== null
                || $this->nullableText($startRaw) !== null
                || $this->nullableText($endRaw) !== null
            ) {
                $hasExplicitHours = true;
            }

            $enabled = $this->bool($enabledRaw);
            $start = $this->nullableText($startRaw);
            $end = $this->nullableText($endRaw);

            $schedule[$day] = [
                'enabled' => $enabled,
                'start' => $enabled ? $start : null,
                'end' => $enabled ? $end : null,
            ];
        }

        if (! $hasExplicitHours) {
            return WorkingHoursCodec::defaultSchedule();
        }

        return WorkingHoursCodec::normalize($schedule);
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function status(mixed $value): string
    {
        $status = Str::of((string) $value)->trim()->lower()->toString();

        return in_array($status, Account::STATUSES, true) ? $status : Account::STATUS_ACTIVE;
    }

    private function normalizeGps(mixed $value): string
    {
        $clean = trim(str_replace('،', ',', (string) $value));

        if ($clean === '' || ! str_contains($clean, ',')) {
            return $clean;
        }

        $parts = array_map('trim', explode(',', $clean, 2));
        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return $clean;
        }

        return number_format((float) $parts[0], 6, '.', '') . ',' . number_format((float) $parts[1], 6, '.', '');
    }

    private function bool(mixed $value): bool
    {
        $text = Str::of((string) $value)->trim()->lower()->toString();

        return in_array($text, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
