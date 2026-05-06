<?php

namespace App\Http\Controllers\Customer\Agent;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Finance\Account;
use App\Http\Requests\Customer\CustomerRequest;
use App\Services\Customer\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentCustomerController extends Controller
{
    private const TYPE_WORKSHOP = 'workshop';
    private const TYPE_RETAIL_STORE = 'retail_store';

    public function __construct(private readonly CustomerService $customerService) {}

    public function commercialStoresIndex(Request $request): View
    {
        return $this->indexByType($request, self::TYPE_RETAIL_STORE, 'agent.commercial-stores.index');
    }

    public function workshopsIndex(Request $request): View
    {
        return $this->indexByType($request, self::TYPE_WORKSHOP, 'agent.workshops.index');
    }

    public function commercialStoresCreate(): View
    {
        return view('agent.commercial-stores.create');
    }

    public function workshopsCreate(): View
    {
        return view('agent.workshops.create');
    }

    public function commercialStoresStore(CustomerRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['type' => self::TYPE_RETAIL_STORE]);
        $this->customerService->create($data);

        return redirect()->route('agent.commercial-stores.index')->with('success', 'تم إضافة المحل التجاري بنجاح.');
    }

    public function workshopsStore(CustomerRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['type' => self::TYPE_WORKSHOP]);
        $this->customerService->create($data);

        return redirect()->route('agent.workshops.index')->with('success', 'تم إضافة الورشة بنجاح.');
    }

    public function commercialStoresEdit(Customer $customer): View
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);

        return view('agent.commercial-stores.edit', compact('customer'));
    }

    public function workshopsEdit(Customer $customer): View
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);

        return view('agent.workshops.edit', compact('customer'));
    }

    public function commercialStoresUpdate(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $data = array_merge($request->validated(), ['type' => self::TYPE_RETAIL_STORE]);
        $this->customerService->update($customer, $data);

        return redirect()->route('agent.commercial-stores.index')->with('success', 'تم تحديث بيانات المحل التجاري.');
    }

    public function workshopsUpdate(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $data = array_merge($request->validated(), ['type' => self::TYPE_WORKSHOP]);
        $this->customerService->update($customer, $data);

        return redirect()->route('agent.workshops.index')->with('success', 'تم تحديث بيانات الورشة.');
    }

    public function commercialStoresDestroy(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $this->customerService->delete($customer);

        return back()->with('success', 'تم حذف المحل التجاري.');
    }

    public function workshopsDestroy(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $this->customerService->delete($customer);

        return back()->with('success', 'تم حذف الورشة.');
    }

    public function commercialStoresToggleStatus(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_RETAIL_STORE);
        $this->customerService->toggleStatus($customer);

        return back()->with('success', 'تم تحديث حالة المحل التجاري.');
    }

    public function workshopsToggleStatus(Customer $customer): RedirectResponse
    {
        $this->ensureType($customer, self::TYPE_WORKSHOP);
        $this->customerService->toggleStatus($customer);

        return back()->with('success', 'تم تحديث حالة الورشة.');
    }

    private function indexByType(Request $request, string $type, string $viewName): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');

        $customers = Customer::query()
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
