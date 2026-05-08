<?php

namespace App\Http\Controllers\Supplier\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierFieldChangeRequest;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Services\Supplier\SupplierService;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSupplierController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $verification = (string) $request->query('verification', '');
        $trashed = (string) $request->query('trashed', '');

        $suppliers = Supplier::query()
            ->with('agentAccount')
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('owner_name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($verification === 'verified', function ($query) {
                $query->where('is_verified', true);
            })
            ->when($verification === 'pending', function ($query) {
                $query->where('is_verified', false)
                    ->whereNotNull('verification_requested_at')
                    ->whereNotNull('verification_requested_by_user_id');
            })
            ->when($verification === 'not_requested', function ($query) {
                $query->where('is_verified', false)
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('verification_requested_at')
                            ->orWhereNull('verification_requested_by_user_id');
                    });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
            'branch_manager_password' => ['nullable', 'string', 'min:6'],
            'phone' => ['required', 'string', 'max:20', new UniqueUserContact('phone')],
        ], [
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
            'branch_manager_password.min' => 'كلمة مرور مدير الفرع يجب أن تكون 6 أحرف على الأقل.',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا.',
        ]);

        $this->supplierService->createSupplier($request->all());

        return redirect()->route('admin.suppliers.index')->with('success', 'تم إضافة الوكيل بنجاح.');
    }

    public function edit(Supplier $supplier): View
    {
        $supplier->load('agentAccount');

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'agentAccount',
            'branches' => function ($query) {
                $query->latest();
            },
            'distributors' => function ($query) {
                $query->latest();
            },
            'distributors.branch',
        ]);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $request->validate([
            'password' => ['nullable', 'string', 'min:6'],
            'branch_manager_password' => ['nullable', 'string', 'min:6'],
        ], [
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
            'branch_manager_password.min' => 'كلمة مرور مدير الفرع يجب أن تكون 6 أحرف على الأقل.',
        ]);

        $this->supplierService->updateSupplier(array_merge($request->all(), [
            'supplier_id' => $supplier->id,
        ]));

        return redirect()->route('admin.suppliers.index')->with('success', 'تم تعديل بيانات الوكيل بنجاح.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->deleteSupplier($supplier->id);

        return redirect()->route('admin.suppliers.index')->with('success', 'تم حذف الوكيل بنجاح.');
    }

    public function restore(int $supplier): RedirectResponse
    {
        $this->supplierService->restoreSupplier($supplier);

        return redirect()->route('admin.suppliers.index')->with('success', 'تم استرجاع الوكيل بنجاح.');
    }

    public function forceDelete(int $supplier): RedirectResponse
    {
        $this->supplierService->forceDeleteSupplier($supplier);

        return redirect()->route('admin.suppliers.index')->with('success', 'تم الحذف النهائي للوكيل بنجاح.');
    }

    public function toggle(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->toggleStatus($supplier->id);

        return redirect()->route('admin.suppliers.index')->with('success', 'تم تحديث حالة الوكيل.');
    }

    public function verify(Supplier $supplier): RedirectResponse
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

    public function approveFieldChange(Supplier $supplier, SupplierFieldChangeRequest $changeRequest): RedirectResponse
    {
        try {
            $this->supplierService->approveFieldChangeRequest($supplier->id, $changeRequest->id, (int) Auth::guard('admin')->id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'تم قبول طلب تعديل الحقل وتطبيق التعديل بنجاح.');
    }

    public function rejectFieldChange(Supplier $supplier, SupplierFieldChangeRequest $changeRequest): RedirectResponse
    {
        try {
            $this->supplierService->rejectFieldChangeRequest($supplier->id, $changeRequest->id, (int) Auth::guard('admin')->id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'تم رفض طلب تعديل الحقل.');
    }
}
