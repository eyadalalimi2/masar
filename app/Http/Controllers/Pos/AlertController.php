<?php

namespace App\Http\Controllers\Pos;

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
        $posId = (int) (Auth::guard('pos')->id() ?? 0);
        $status = request()->query('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $alerts = $this->webAlertService->paginate('pos_account', $posId, 20, $status);
        $unreadCount = $this->webAlertService->unreadCount('pos_account', $posId);

        return view('pos.alerts.index', compact('alerts', 'unreadCount', 'status'));
    }

    public function markAsRead(int $alert): RedirectResponse
    {
        $posId = (int) (Auth::guard('pos')->id() ?? 0);
        $this->webAlertService->markAsRead('pos_account', $posId, $alert);

        return back()->with('success', 'تم تعليم التنبيه كمقروء.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $posId = (int) (Auth::guard('pos')->id() ?? 0);
        $affected = $this->webAlertService->markAllAsRead('pos_account', $posId);

        return back()->with('success', 'تم تعليم ' . $affected . ' تنبيه كمقروء.');
    }
}
