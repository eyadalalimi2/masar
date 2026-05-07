<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Customer\Customer;
use App\Models\Customer\Workshop;
use App\Models\Finance\Account;
use App\Models\Pos;
use App\Models\Supplier\Supplier;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthVerificationController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');

        $accounts = $this->collectAccounts($search, $type, $status);
        $verifiedAccountsCount = $accounts->where('verification_state', 'verified')->count();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $accounts->count();
        $items = $accounts->slice(($page - 1) * $perPage, $perPage)->values();
        $accounts = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        $pendingSuppliers = Supplier::query()
            ->where('is_verified', false)
            ->whereNotNull('verification_requested_at')
            ->whereNotNull('verification_requested_by_user_id')
            ->with('agentAccount:id,supplier_id,name,phone')
            ->latest('verification_requested_at')
            ->limit(12)
            ->get([
                'id',
                'owner_name',
                'business_name',
                'phone',
                'is_verified',
                'verification_requested_at',
                'verification_requested_by_user_id',
            ]);

        $stats = [
            'total_accounts' => $this->totalAccountsCount(),
            'active_accounts' => $this->totalStatusCount(Account::STATUS_ACTIVE),
            'inactive_accounts' => $this->totalStatusCount(Account::STATUS_INACTIVE),
            'pending_supplier_verifications' => Supplier::query()
                ->where('is_verified', false)
                ->whereNotNull('verification_requested_at')
                ->whereNotNull('verification_requested_by_user_id')
                ->count()
                + Customer::query()
                ->whereIn('type', ['retail_store', 'workshop', 'wholesale_trader'])
                ->where('is_verified', false)
                ->whereNotNull('verification_requested_at')
                ->whereNotNull('verification_requested_by_user_id')
                ->count(),
            'verified_suppliers' => Supplier::query()->where('is_verified', true)->count(),
        ];

        return view('admin.auth-verification.index', compact('accounts', 'pendingSuppliers', 'stats', 'verifiedAccountsCount'));
    }

    public function toggleAccountStatus(string $type, int $id): RedirectResponse
    {
        $config = $this->accountTypes()[$type] ?? null;
        if (! is_array($config)) {
            abort(404);
        }

        $modelClass = $config['model'];
        $record = $modelClass::query()->findOrFail($id);

        $record->update([
            'status' => $record->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'تم تحديث حالة الحساب بنجاح.');
    }

    public function verifySupplier(Supplier $supplier): RedirectResponse
    {
        if ($supplier->is_verified) {
            return back()->with('success', 'الوكيل موثّق بالفعل.');
        }

        if (! $supplier->has_verification_request) {
            return back()->with('error', 'لا يوجد طلب توثيق صالح مرسل من الوكيل.');
        }

        try {
            $this->supplierService->verifySupplier($supplier->id, (int) Auth::guard('admin')->id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'تم توثيق بيانات الوكيل بنجاح.');
    }

    public function unverifySupplier(Supplier $supplier): RedirectResponse
    {
        if (! $supplier->is_verified) {
            return back()->with('success', 'الوكيل غير موثّق بالفعل.');
        }

        $this->supplierService->unverifySupplier($supplier->id);

        return back()->with('success', 'تم إلغاء توثيق الوكيل بنجاح.');
    }

    public function verifyCustomer(Customer $customer): RedirectResponse
    {
        if (! in_array($customer->type, ['retail_store', 'workshop', 'wholesale_trader'], true)) {
            abort(404);
        }

        if ($customer->is_verified) {
            return back()->with('success', 'الحساب موثّق بالفعل.');
        }

        $customer->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by_user_id' => (int) Auth::guard('admin')->id(),
            'verification_requested_at' => null,
            'verification_requested_by_user_id' => null,
        ]);

        return back()->with('success', 'تم توثيق الحساب بنجاح.');
    }

    public function unverifyCustomer(Customer $customer): RedirectResponse
    {
        if (! in_array($customer->type, ['retail_store', 'workshop', 'wholesale_trader'], true)) {
            abort(404);
        }

        if (! $customer->is_verified) {
            return back()->with('success', 'الحساب غير موثّق بالفعل.');
        }

        $customer->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by_user_id' => null,
            'verification_requested_at' => null,
            'verification_requested_by_user_id' => null,
        ]);

        return back()->with('success', 'تم إلغاء توثيق الحساب بنجاح.');
    }

    public function showDocuments(string $type, int $id): View
    {
        $config = $this->accountTypes()[$type] ?? null;
        if (! is_array($config)) {
            abort(404);
        }

        $modelClass = $config['model'];
        $account = $modelClass::query()->findOrFail($id);

        $documents = [
            'national_id_number' => null,
            'national_id_image_url' => null,
            'commercial_reg_number' => null,
            'commercial_reg_image_url' => null,
            'license_number' => null,
            'license_image_url' => null,
        ];

        $sourceLabel = 'لا توجد وثائق توثيق لهذا النوع من الحسابات حاليًا.';

        if ($type === 'agent') {
            $supplier = Supplier::query()->find($account->supplier_id);

            if ($supplier) {
                $documents = [
                    'national_id_number' => $supplier->national_id_number,
                    'national_id_image_url' => $supplier->national_id_image_url,
                    'commercial_reg_number' => $supplier->commercial_reg_number,
                    'commercial_reg_image_url' => $supplier->commercial_reg_image_url,
                    'license_number' => $supplier->license_number,
                    'license_image_url' => $supplier->license_image_url,
                ];
                $sourceLabel = 'البيانات مرتبطة بحساب المورد الخاص بالوكيل.';
            }
        } elseif (in_array($type, ['commercial_store', 'workshop', 'wholesale_trader'], true)) {
            $customerType = match ($type) {
                'commercial_store' => 'retail_store',
                'workshop' => 'workshop',
                default => 'wholesale_trader',
            };
            $customer = null;

            if ($type === 'wholesale_trader' && $account instanceof Customer && $account->type === 'wholesale_trader') {
                $customer = $account;
            } elseif ((int) ($account->customer_id ?? 0) > 0) {
                $customer = Customer::query()
                    ->whereKey((int) $account->customer_id)
                    ->where('type', $customerType)
                    ->first();
            }

            if ($customer) {
                $documents = [
                    'national_id_number' => $customer->national_id_number,
                    'national_id_image_url' => $customer->national_id_image_url,
                    'commercial_reg_number' => $customer->commercial_reg_number,
                    'commercial_reg_image_url' => $customer->commercial_reg_image_url,
                    'license_number' => $customer->license_number,
                    'license_image_url' => $customer->license_image_url,
                ];
                $sourceLabel = 'البيانات مأخوذة من ملف العميل المرتبط بالحساب.';
            }
        }

        return view('admin.auth-verification.documents', [
            'accountType' => $type,
            'accountTypeLabel' => $this->accountTypeLabel($type),
            'account' => $account,
            'documents' => $documents,
            'sourceLabel' => $sourceLabel,
        ]);
    }

    private function collectAccounts(string $search, string $type, string $status)
    {
        $allowedStatuses = [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE];
        $accountTypes = $this->accountTypes();
        $accounts = collect();

        foreach ($accountTypes as $key => $config) {
            if ($type !== '' && $type !== $key) {
                continue;
            }

            $modelClass = $config['model'];
            $nameColumn = $config['name_column'];
            $phoneColumn = $config['phone_column'];
            $selectColumns = [
                'id',
                DB::raw($this->quoteColumn($nameColumn) . ' as account_name'),
                DB::raw($this->quoteColumn($phoneColumn) . ' as account_phone'),
                'status',
                'created_at',
            ];

            if ($key === 'agent') {
                $selectColumns[] = 'supplier_id';
            }

            if (isset($config['customer_id_select']) && is_string($config['customer_id_select'])) {
                $selectColumns[] = DB::raw($config['customer_id_select'] . ' as customer_id');
            } elseif (in_array($key, ['commercial_store', 'workshop', 'wholesale_trader'], true)) {
                $selectColumns[] = 'customer_id';
            }

            $query = $modelClass::query()
                ->select($selectColumns);

            if (isset($config['customer_type'])) {
                $query->where('type', $config['customer_type']);
            }

            if ($search !== '') {
                $query->where(function ($subQuery) use ($search, $nameColumn, $phoneColumn) {
                    $subQuery->where($nameColumn, 'like', '%' . $search . '%')
                        ->orWhere($phoneColumn, 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            }

            if (in_array($status, $allowedStatuses, true)) {
                $query->where('status', $status);
            }

            $rows = $query->latest()->get();
            $supplierVerificationMap = collect();
            $customerVerificationMap = collect();

            if ($key === 'agent') {
                $supplierIds = $rows
                    ->pluck('supplier_id')
                    ->filter(fn($id) => $id !== null)
                    ->unique()
                    ->values();

                if ($supplierIds->isNotEmpty()) {
                    $supplierVerificationMap = Supplier::query()
                        ->whereIn('id', $supplierIds)
                        ->get([
                            'id',
                            'is_verified',
                            'verification_requested_at',
                            'verification_requested_by_user_id',
                        ])
                        ->keyBy('id');
                }
            }

            if (in_array($key, ['commercial_store', 'workshop', 'wholesale_trader'], true)) {
                $customerType = match ($key) {
                    'commercial_store' => 'retail_store',
                    'workshop' => 'workshop',
                    default => 'wholesale_trader',
                };
                $customerIds = $rows
                    ->pluck('customer_id')
                    ->filter(fn($id) => $id !== null)
                    ->unique()
                    ->values();

                if ($customerIds->isNotEmpty()) {
                    $customerVerificationMap = Customer::query()
                        ->whereIn('id', $customerIds)
                        ->where('type', $customerType)
                        ->get([
                            'id',
                            'is_verified',
                            'verification_requested_at',
                            'verification_requested_by_user_id',
                        ])
                        ->keyBy('id');
                }
            }

            $items = $rows->map(function ($item) use ($key, $config, $supplierVerificationMap, $customerVerificationMap) {
                $verificationState = 'not_applicable';

                if ($key === 'agent') {
                    $supplier = $supplierVerificationMap->get((int) ($item->supplier_id ?? 0));

                    if ($supplier?->is_verified) {
                        $verificationState = 'verified';
                    } elseif (
                        $supplier
                        && $supplier->verification_requested_at !== null
                        && $supplier->verification_requested_by_user_id !== null
                    ) {
                        $verificationState = 'pending';
                    } else {
                        $verificationState = 'unverified';
                    }
                } elseif (in_array($key, ['commercial_store', 'workshop', 'wholesale_trader'], true)) {
                    $customer = $customerVerificationMap->get((int) ($item->customer_id ?? 0));
                    $verificationState = $this->resolveCustomerVerificationState($customer);
                }

                return (object) [
                    'row_key' => $key . ':' . $item->id,
                    'type' => $key,
                    'type_label' => $config['label'],
                    'id' => $item->id,
                    'supplier_id' => $key === 'agent' ? (int) ($item->supplier_id ?? 0) : null,
                    'customer_id' => in_array($key, ['commercial_store', 'workshop', 'wholesale_trader'], true) ? (int) ($item->customer_id ?? 0) : null,
                    'name' => (string) $item->account_name,
                    'phone' => (string) $item->account_phone,
                    'status' => (string) $item->status,
                    'verification_state' => $verificationState,
                    'created_at' => $item->created_at,
                ];
            });

            $accounts = $accounts->concat($items);
        }

        return $accounts->sortByDesc('created_at')->values();
    }

    private function totalAccountsCount(): int
    {
        return collect($this->accountTypes())
            ->sum(fn(array $config) => (int) ($config['model'])::query()->count());
    }

    private function totalStatusCount(string $status): int
    {
        return collect($this->accountTypes())
            ->sum(fn(array $config) => (int) ($config['model'])::query()->where('status', $status)->count());
    }

    private function accountTypes(): array
    {
        return [
            'agent' => [
                'label' => 'وكيل',
                'model' => Agent::class,
                'name_column' => 'name',
                'phone_column' => 'phone',
            ],
            'commercial_store' => [
                'label' => 'المحلات التجارية',
                'model' => Pos::class,
                'name_column' => 'name',
                'phone_column' => 'phone',
            ],
            'workshop' => [
                'label' => 'ورشة صيانة',
                'model' => Workshop::class,
                'name_column' => 'name',
                'phone_column' => 'phone',
            ],
            'wholesale_trader' => [
                'label' => 'تاجر جملة',
                'model' => Customer::class,
                'name_column' => 'name',
                'phone_column' => 'phone',
                'customer_id_select' => 'id',
                'customer_type' => 'wholesale_trader',
            ],
        ];
    }

    private function accountTypeLabel(string $type): string
    {
        return $this->accountTypes()[$type]['label'] ?? $type;
    }

    private function quoteColumn(string $column): string
    {
        return Str::replace('.', '`.`', '`' . $column . '`');
    }

    private function resolveCustomerVerificationState(?Customer $customer): string
    {
        if (! $customer) {
            return 'unverified';
        }

        if ((bool) $customer->is_verified) {
            return 'verified';
        }

        if ($customer->verification_requested_at !== null && $customer->verification_requested_by_user_id !== null) {
            return 'pending';
        }

        return 'unverified';
    }
}
