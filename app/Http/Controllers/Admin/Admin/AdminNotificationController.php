<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Support\OptionLists;
use App\Models\Admin\Broadcast;
use App\Models\Notifications\WebAlert;
use App\Models\Admin\SystemSetting;
use App\Models\Admin;
use App\Models\Orders\Order;
use App\Services\Notifications\AdminBroadcastService;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly WebAlertService $webAlertService,
        private readonly AdminBroadcastService $adminBroadcastService,
    ) {}

    public function index(Request $request): View
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $alertType = (string) $request->query('type', 'all');
        if (! in_array($alertType, ['all', 'delay', 'smart', 'broadcast', 'other'], true)) {
            $alertType = 'all';
        }

        $alertSource = (string) $request->query('source', 'all');
        if (! in_array($alertSource, ['all', 'orders', 'dispatch', 'system'], true)) {
            $alertSource = 'all';
        }

        $alertsQuery = WebAlert::query()
            ->where('recipient_type', 'admin')
            ->where('recipient_id', $adminId)
            ->when($status === 'unread', fn($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn($query) => $query->whereNotNull('read_at'))
            ->latest();

        if ($alertType !== 'all') {
            $alertsQuery->where(function ($query) use ($alertType) {
                if ($alertType === 'delay') {
                    $query->where('title', 'like', '%تأخير%')
                        ->orWhere('data', 'like', '%delay%');

                    return;
                }

                if ($alertType === 'smart') {
                    $query->where('title', 'like', '%تحذير%')
                        ->orWhere('data', 'like', '%smart_alert%');

                    return;
                }

                if ($alertType === 'broadcast') {
                    $query->where('data', 'like', '%admin_broadcast_result%')
                        ->orWhere('title', 'like', '%إشعار مركزي%')
                        ->orWhere('title', 'like', '%مجدولة%');

                    return;
                }

                $query->where('title', 'not like', '%تأخير%')
                    ->where('title', 'not like', '%تحذير%')
                    ->where('title', 'not like', '%إشعار مركزي%')
                    ->where('data', 'not like', '%delay%')
                    ->where('data', 'not like', '%smart_alert%')
                    ->where('data', 'not like', '%admin_broadcast_result%');
            });
        }

        if ($alertSource !== 'all') {
            $alertsQuery->where(function ($query) use ($alertSource) {
                if ($alertSource === 'orders') {
                    $query->where('data', 'like', '%"source":"orders"%')
                        ->orWhere('data', 'like', '%order%')
                        ->orWhere('data', 'like', '%delay%')
                        ->orWhere('title', 'like', '%طلب%');

                    return;
                }

                if ($alertSource === 'dispatch') {
                    $query->where('data', 'like', '%"source":"dispatch"%')
                        ->orWhere('data', 'like', '%dispatch%')
                        ->orWhere('data', 'like', '%distributor%')
                        ->orWhere('title', 'like', '%توزيع%')
                        ->orWhere('title', 'like', '%إسناد%');

                    return;
                }

                $query->where('data', 'like', '%"source":"system"%')
                    ->orWhere('data', 'like', '%smart_alert%')
                    ->orWhere('data', 'like', '%admin_broadcast_result%')
                    ->orWhere('title', 'like', '%تحذير%')
                    ->orWhere('title', 'like', '%إشعار مركزي%');
            });
        }

        $alerts = $alertsQuery->paginate(20)->withQueryString();
        $unreadCount = $this->webAlertService->unreadCount('admin', $adminId);

        $todayAlerts = WebAlert::query()
            ->where('recipient_type', 'admin')
            ->where('recipient_id', $adminId)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $dailyAlertSummary = [
            'total' => (int) $todayAlerts->count(),
            'delay' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertType($alert) === 'delay')->count(),
            'smart' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertType($alert) === 'smart')->count(),
            'broadcast' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertType($alert) === 'broadcast')->count(),
            'other' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertType($alert) === 'other')->count(),
        ];

        $dailySourceSummary = [
            'orders' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertSource($alert) === 'orders')->count(),
            'dispatch' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertSource($alert) === 'dispatch')->count(),
            'system' => (int) $todayAlerts->filter(fn(WebAlert $alert) => $this->classifyAlertSource($alert) === 'system')->count(),
        ];

        $broadcasts = Broadcast::query()->latest()->paginate(12, ['*'], 'broadcasts_page')->withQueryString();
        $smartThresholds = SystemSetting::getValue('smart_alerts', [
            'order_drop_percentage' => 30,
            'stale_pending_hours' => 24,
        ]);

        return view('admin.notifications.index', compact(
            'alerts',
            'unreadCount',
            'status',
            'alertType',
            'alertSource',
            'dailyAlertSummary',
            'dailySourceSummary',
            'broadcasts',
            'smartThresholds'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
            'target_type' => ['required', 'in:' . implode(',', OptionLists::BROADCAST_TARGET_TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'send_mode' => ['required', 'in:now,scheduled'],
            'scheduled_for' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        $sendMode = (string) $data['send_mode'];
        $scheduleAt = $sendMode === 'scheduled' ? ($data['scheduled_for'] ?? null) : null;

        $broadcast = Broadcast::query()->create([
            'title' => $data['title'],
            'message' => $data['message'],
            'target_type' => $data['target_type'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'scheduled_for' => $scheduleAt,
            'created_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        $sentCount = 0;
        if ($broadcast->is_active && $sendMode === 'now') {
            $sentCount = $this->adminBroadcastService->dispatchBroadcast($broadcast);
        }

        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        if ($adminId > 0) {
            $this->webAlertService->create(
                'admin',
                $adminId,
                $sendMode === 'now' ? 'تم نشر إشعار مركزي' : 'تمت جدولة إشعار مركزي',
                $sendMode === 'now'
                    ? 'تم نشر الإشعار إلى ' . $sentCount . ' مستلم.'
                    : 'تمت جدولة الإشعار للتنفيذ لاحقًا.',
                [
                    'type' => 'admin_broadcast_result',
                    'broadcast_id' => $broadcast->id,
                    'sent_count' => $sentCount,
                    'send_mode' => $sendMode,
                ]
            );
        }

        $message = $sendMode === 'now'
            ? 'تم إنشاء الإشعار المركزي بنجاح. عدد المستلمين: ' . $sentCount
            : 'تم حفظ الإشعار كرسالة مجدولة.';

        return redirect()->route('admin.notifications.index')->with('success', $message);
    }

    public function toggle(Broadcast $broadcast): RedirectResponse
    {
        $broadcast->update(['is_active' => ! $broadcast->is_active]);

        return back()->with('success', $broadcast->is_active ? 'تم تفعيل الرسالة.' : 'تم إيقاف الرسالة.');
    }

    public function dispatchNow(Broadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->is_active) {
            return back()->withErrors(['broadcast' => 'الرسالة غير مفعلة. قم بتفعيلها أولاً.']);
        }

        if ($broadcast->dispatched_at !== null) {
            return back()->with('success', 'تم إرسال هذه الرسالة سابقًا.');
        }

        $sentCount = $this->adminBroadcastService->dispatchBroadcast($broadcast);

        return back()->with('success', 'تم إرسال الرسالة الآن إلى ' . $sentCount . ' مستلم.');
    }

    public function dispatchScheduled(): RedirectResponse
    {
        $result = $this->adminBroadcastService->queueDueScheduled();

        return back()->with('success', 'تمت جدولة ' . $result['queued_broadcasts'] . ' رسالة مجدولة للإرسال عبر Queue.');
    }

    public function generateSmartAlerts(): RedirectResponse
    {
        $smartThresholds = SystemSetting::getValue('smart_alerts', [
            'order_drop_percentage' => 30,
            'stale_pending_hours' => 24,
        ]);

        $dropThreshold = (float) ($smartThresholds['order_drop_percentage'] ?? 30);
        $staleHours = (int) ($smartThresholds['stale_pending_hours'] ?? 24);

        $currentPeriod = Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $previousPeriod = Order::query()
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        $stalePending = Order::query()
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('created_at', '<=', now()->subHours($staleHours))
            ->count();

        $alertsGenerated = 0;

        if ($previousPeriod > 0) {
            $dropPercent = (($previousPeriod - $currentPeriod) / $previousPeriod) * 100;
            if ($dropPercent >= $dropThreshold) {
                $this->notifyActiveAdmins(
                    'تحذير انخفاض النشاط',
                    'انخفضت الطلبات بنسبة ' . number_format($dropPercent, 1) . '% خلال آخر 7 أيام.'
                );
                $alertsGenerated++;
            }
        }

        if ($stalePending > 0) {
            $this->notifyActiveAdmins(
                'تحذير تشغيل',
                'يوجد ' . $stalePending . ' طلبات متأخرة تتجاوز ' . $staleHours . ' ساعة.'
            );
            $alertsGenerated++;
        }

        return back()->with('success', 'تم توليد ' . $alertsGenerated . ' Smart Alerts.');
    }

    public function markAsRead(int $alert): RedirectResponse
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        $this->webAlertService->markAsRead('admin', $adminId, $alert);

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        $affected = $this->webAlertService->markAllAsRead('admin', $adminId);

        return back()->with('success', 'تم تعليم ' . $affected . ' إشعار كمقروء.');
    }

    private function notifyActiveAdmins(string $title, string $body): void
    {
        $adminIds = Admin::query()->where('status', 'active')->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->webAlertService->create('admin', (int) $adminId, $title, $body, [
                'type' => 'smart_alert',
            ]);
        }
    }

    private function classifyAlertType(WebAlert $alert): string
    {
        $title = (string) $alert->title;
        $data = (array) ($alert->data ?? []);
        $dataType = (string) ($data['type'] ?? '');

        if (str_contains($title, 'تأخير') || str_contains($dataType, 'delay')) {
            return 'delay';
        }

        if (str_contains($title, 'تحذير') || $dataType === 'smart_alert') {
            return 'smart';
        }

        if (str_contains($title, 'إشعار مركزي') || str_contains($title, 'مجدولة') || $dataType === 'admin_broadcast_result') {
            return 'broadcast';
        }

        return 'other';
    }

    private function classifyAlertSource(WebAlert $alert): string
    {
        $title = (string) $alert->title;
        $data = (array) ($alert->data ?? []);
        $source = (string) ($data['source'] ?? '');
        $dataType = (string) ($data['type'] ?? '');

        if ($source === 'orders' || str_contains($dataType, 'order') || str_contains($dataType, 'delay') || str_contains($dataType, 'stage') || str_contains($title, 'طلب')) {
            return 'orders';
        }

        if ($source === 'dispatch' || str_contains($dataType, 'dispatch') || str_contains($dataType, 'distributor') || str_contains($title, 'توزيع') || str_contains($title, 'إسناد')) {
            return 'dispatch';
        }

        return 'system';
    }
}
