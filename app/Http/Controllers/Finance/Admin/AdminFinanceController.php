<?php

namespace App\Http\Controllers\Finance\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Models\Finance\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFinanceController extends Controller
{
    public function payments(Request $request): View
    {
        $status = (string) $request->get('status', '');
        $trashed = (string) $request->get('trashed', '');

        $payments = Payment::query()
            ->with(['order.supplier', 'order.distributor'])
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.payments.index', compact('payments'));
    }

    public function accounts(): View
    {
        $accounts = CustomerAccount::with(['transactions' => function ($query) {
            $query->withTrashed()->latest()->limit(10);
        }, 'customer'])->latest()->paginate(15);

        return view('admin.finance.accounts.index', compact('accounts'));
    }

    public function destroyPayment(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('admin.payments.index')->with('success', 'تم حذف الدفعة بنجاح.');
    }

    public function restorePayment(int $payment): RedirectResponse
    {
        $model = Payment::withTrashed()->findOrFail($payment);
        if ($model->trashed()) {
            $model->restore();
        }

        return redirect()->route('admin.payments.index')->with('success', 'تم استرجاع الدفعة بنجاح.');
    }

    public function forceDeletePayment(int $payment): RedirectResponse
    {
        $model = Payment::withTrashed()->findOrFail($payment);
        $model->forceDelete();

        return redirect()->route('admin.payments.index')->with('success', 'تم الحذف النهائي للدفعة بنجاح.');
    }

    public function destroyTransaction(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'تم حذف الحركة بنجاح.');
    }

    public function restoreTransaction(int $transaction): RedirectResponse
    {
        $model = Transaction::withTrashed()->findOrFail($transaction);
        if ($model->trashed()) {
            $model->restore();
        }

        return redirect()->route('admin.accounts.index')->with('success', 'تم استرجاع الحركة بنجاح.');
    }

    public function forceDeleteTransaction(int $transaction): RedirectResponse
    {
        $model = Transaction::withTrashed()->findOrFail($transaction);
        $model->forceDelete();

        return redirect()->route('admin.accounts.index')->with('success', 'تم الحذف النهائي للحركة بنجاح.');
    }
}
