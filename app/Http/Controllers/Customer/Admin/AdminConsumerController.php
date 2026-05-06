<?php

namespace App\Http\Controllers\Customer\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Http\Requests\Customer\ConsumerRequest;
use App\Models\Consumer;
use App\Services\Customer\ConsumerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminConsumerController extends Controller
{
    public function __construct(private readonly ConsumerService $consumerService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');

        $consumers = Consumer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('whatsapp', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE], true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.consumers.index', compact('consumers', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.consumers.create');
    }

    public function store(ConsumerRequest $request): RedirectResponse
    {
        $this->consumerService->create($request->validated());

        return redirect()->route('admin.consumers.index')->with('success', 'تم إضافة المستهلك بنجاح.');
    }

    public function edit(Consumer $consumer): View
    {
        return view('admin.consumers.edit', compact('consumer'));
    }

    public function update(ConsumerRequest $request, Consumer $consumer): RedirectResponse
    {
        $this->consumerService->update($consumer, $request->validated());

        return redirect()->route('admin.consumers.index')->with('success', 'تم تحديث بيانات المستهلك.');
    }

    public function destroy(Consumer $consumer): RedirectResponse
    {
        $this->consumerService->delete($consumer);

        return back()->with('success', 'تم حذف المستهلك.');
    }

    public function toggleStatus(Consumer $consumer): RedirectResponse
    {
        $this->consumerService->toggleStatus($consumer);

        return back()->with('success', 'تم تحديث حالة المستهلك.');
    }
}
