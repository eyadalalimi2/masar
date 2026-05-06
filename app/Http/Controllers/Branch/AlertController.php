<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function index(): View
    {
        $account = Auth::guard('branch')->user();
        $branch = $this->currentBranch();
        $accountId = (int) ($account->id ?? 0);
        $status = request()->query('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $alerts = $this->webAlertService->paginate('branch_account', $accountId, 20, $status);
        $unreadCount = $this->webAlertService->unreadCount('branch_account', $accountId);

        return view('branch.alerts.index', compact('branch', 'alerts', 'unreadCount', 'status'));
    }

    public function markAsRead(int $alert): RedirectResponse
    {
        $account = Auth::guard('branch')->user();
        $accountId = (int) ($account->id ?? 0);

        $this->webAlertService->markAsRead('branch_account', $accountId, $alert);

        return back()->with('success', 'تم تعليم التنبيه كمقروء.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $account = Auth::guard('branch')->user();
        $accountId = (int) ($account->id ?? 0);

        $affected = $this->webAlertService->markAllAsRead('branch_account', $accountId);

        return back()->with('success', 'تم تعليم ' . $affected . ' تنبيه كمقروء.');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return Branch::query()->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return Branch::query()->where('phone', $phone)->firstOrFail();
    }
}
