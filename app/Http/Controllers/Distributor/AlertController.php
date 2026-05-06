<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function index(): View
    {
        $accountId = (int) (Auth::guard('distributor')->id() ?? 0);

        $status = request()->query('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $alerts = $this->webAlertService->paginate('distributor_account', $accountId, 20, $status);
        $unreadCount = $this->webAlertService->unreadCount('distributor_account', $accountId);

        return view('distributor.alerts.index', compact('alerts', 'unreadCount', 'status'));
    }

    public function markAsRead(int $alert): RedirectResponse
    {
        $accountId = (int) (Auth::guard('distributor')->id() ?? 0);
        $this->webAlertService->markAsRead('distributor_account', $accountId, $alert);

        return back()->with('success', 'تم تعليم التنبيه كمقروء.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $accountId = (int) (Auth::guard('distributor')->id() ?? 0);
        $affected = $this->webAlertService->markAllAsRead('distributor_account', $accountId);

        return back()->with('success', 'تم تعليم ' . $affected . ' تنبيه كمقروء.');
    }
}
