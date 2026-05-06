<?php

namespace App\Http\Controllers\Agent;

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
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);
        $status = request()->query('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $alerts = $this->webAlertService->paginate('agent', $agentId, 20, $status);
        $unreadCount = $this->webAlertService->unreadCount('agent', $agentId);

        return view('agent.alerts.index', compact('alerts', 'unreadCount', 'status'));
    }

    public function markAsRead(int $alert): RedirectResponse
    {
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);

        $this->webAlertService->markAsRead('agent', $agentId, $alert);

        return back()->with('success', 'تم تعليم التنبيه كمقروء.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);

        $affected = $this->webAlertService->markAllAsRead('agent', $agentId);

        return back()->with('success', 'تم تعليم ' . $affected . ' تنبيه كمقروء.');
    }
}
