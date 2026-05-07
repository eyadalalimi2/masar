<?php

namespace App\Http\Controllers\Customer\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Customer\Customer;
use App\Http\Requests\Customer\CustomerRequest;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Services\Customer\CustomerService;
use App\Support\WorkingHoursCodec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    private const TYPE_WORKSHOP = 'workshop';
    private const TYPE_RETAIL_STORE = 'retail_store';
    private const TYPE_WHOLESALE_TRADER = 'wholesale_trader';

    public function __construct(private readonly CustomerService $customerService) {}

    public function commercialStoresIndex(Request $request): View
    {
        return $this->indexByType($request, self::TYPE_RETAIL_STORE, 'admin.commercial-stores.index');
    }

    public function workshopsIndex(Request $request): View
    {
        return $this->indexByType($request, self::TYPE_WORKSHOP, 'admin.workshops.index');
    }

    public function wholesaleTradersIndex(Request $request): View
    {
        return $this->indexByType($request, self::TYPE_WHOLESALE_TRADER, 'admin.wholesale-traders.index');
    }

    public function commercialStoresCreate(): View
    {
        return view('admin.commercial-stores.create');
    }

    public function workshopsCreate(): View
    {
        return view('admin.workshops.create');
    }

    public function wholesaleTradersCreate(): View
    {
        return view('admin.wholesale-traders.create');
    }

    public function commercialStoresStore(CustomerRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['type' => self::TYPE_RETAIL_STORE]);
        $this->customerService->create($data);

        return redirect()->route('admin.commercial-stores.index')->with('success', 'تم إضافة المحل التجاري بنجاح.');
    }

    public function workshopsStore(CustomerRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['type' => self::TYPE_WORKSHOP]);
        $this->customerService->create($data);

        return redirect()->route('admin.workshops.index')->with('success', 'تم إضافة الورشة بنجاح.');
    }

    public function wholesaleTradersStore(CustomerRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['type' => self::TYPE_WHOLESALE_TRADER]);
        $this->customerService->create($data);

        return redirect()->route('admin.wholesale-traders.index')->with('success', 'تم إضافة تاجر الجملة بنجاح.');
    }

    public function commercialStoresEdit(Customer $customer): View
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);

        return view('admin.commercial-stores.edit', compact('customer'));
    }

    public function workshopsEdit(Customer $customer): View
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);

        return view('admin.workshops.edit', compact('customer'));
    }

    public function wholesaleTradersEdit(Customer $customer): View
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);

        return view('admin.wholesale-traders.edit', compact('customer'));
    }

    public function commercialStoresUpdate(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $data = array_merge($request->validated(), ['type' => self::TYPE_RETAIL_STORE]);
        $this->customerService->update($customer, $data);

        return redirect()->route('admin.commercial-stores.index')->with('success', 'تم تحديث بيانات المحل التجاري.');
    }

    public function workshopsUpdate(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $data = array_merge($request->validated(), ['type' => self::TYPE_WORKSHOP]);
        $this->customerService->update($customer, $data);

        return redirect()->route('admin.workshops.index')->with('success', 'تم تحديث بيانات الورشة.');
    }

    public function wholesaleTradersUpdate(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);
        $data = array_merge($request->validated(), ['type' => self::TYPE_WHOLESALE_TRADER]);
        $this->customerService->update($customer, $data);

        return redirect()->route('admin.wholesale-traders.index')->with('success', 'تم تحديث بيانات تاجر الجملة.');
    }

    public function commercialStoresDestroy(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $this->customerService->delete($customer);

        return back()->with('success', 'تم حذف المحل التجاري.');
    }

    public function commercialStoresRestore(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_RETAIL_STORE);
        $this->customerService->restore($model);

        return back()->with('success', 'تم استرجاع المحل التجاري بنجاح.');
    }

    public function commercialStoresForceDestroy(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_RETAIL_STORE);
        $this->customerService->forceDelete($model);

        return back()->with('success', 'تم الحذف النهائي للمحل التجاري بنجاح.');
    }

    public function workshopsDestroy(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $this->customerService->delete($customer);

        return back()->with('success', 'تم حذف الورشة.');
    }

    public function wholesaleTradersDestroy(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);
        $this->customerService->delete($customer);

        return back()->with('success', 'تم حذف تاجر الجملة.');
    }

    public function workshopsRestore(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_WORKSHOP);
        $this->customerService->restore($model);

        return back()->with('success', 'تم استرجاع الورشة بنجاح.');
    }

    public function wholesaleTradersRestore(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_WHOLESALE_TRADER);
        $this->customerService->restore($model);

        return back()->with('success', 'تم استرجاع تاجر الجملة بنجاح.');
    }

    public function workshopsForceDestroy(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_WORKSHOP);
        $this->customerService->forceDelete($model);

        return back()->with('success', 'تم الحذف النهائي للورشة بنجاح.');
    }

    public function wholesaleTradersForceDestroy(int $customer): RedirectResponse
    {
        $model = Customer::withTrashed()->findOrFail($customer);
        $this->ensureType($model, self::TYPE_WHOLESALE_TRADER);
        $this->customerService->forceDelete($model);

        return back()->with('success', 'تم الحذف النهائي لتاجر الجملة بنجاح.');
    }

    public function commercialStoresToggleStatus(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $this->customerService->toggleStatus($customer);

        return back()->with('success', 'تم تحديث حالة المحل التجاري.');
    }

    public function commercialStoresRemoveImage(Customer $customer, int $imageIndex): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);

        $removed = $this->customerService->removeStoreImageByIndex($customer, $imageIndex);

        if (! $removed) {
            return back()->withErrors(['store_images' => 'الصورة المحددة غير موجودة.']);
        }

        return back()->with('success', 'تم حذف صورة المحل بنجاح.');
    }

    public function commercialStoresUpdateWorkingHours(WorkingHoursRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = WorkingHoursCodec::encode($workingHours);
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        return back()->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function workshopsToggleStatus(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $this->customerService->toggleStatus($customer);

        return back()->with('success', 'تم تحديث حالة الورشة.');
    }

    public function wholesaleTradersToggleStatus(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);
        $this->customerService->toggleStatus($customer);

        return back()->with('success', 'تم تحديث حالة تاجر الجملة.');
    }

    public function wholesaleTradersRemoveImage(Customer $customer, int $imageIndex): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);

        $removed = $this->customerService->removeStoreImageByIndex($customer, $imageIndex);

        if (! $removed) {
            return back()->withErrors(['store_images' => 'الصورة المحددة غير موجودة.']);
        }

        return back()->with('success', 'تم حذف صورة تاجر الجملة بنجاح.');
    }

    public function wholesaleTradersUpdateWorkingHours(WorkingHoursRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WHOLESALE_TRADER);

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = WorkingHoursCodec::encode($workingHours);
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        return back()->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function workshopsUpdateWorkingHours(WorkingHoursRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = WorkingHoursCodec::encode($workingHours);
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        return back()->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    private function indexByType(Request $request, string $type, string $viewName): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');
        $trashed = (string) $request->get('trashed', '');

        $customers = Customer::query()
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->where('type', $type)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('owner_name', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view($viewName, compact('customers', 'search', 'status'));
    }

    private function ensureType(Customer $customer, string $expectedType): void
    {
        if ($customer->type !== $expectedType) {
            abort(404);
        }
    }
}
