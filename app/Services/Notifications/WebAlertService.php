<?php

namespace App\Services\Notifications;

use App\Jobs\Notifications\SendWebAlertPushJob;
use App\Models\Notifications\WebAlert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WebAlertService
{
    public function create(string $recipientType, int $recipientId, string $title, string $body, array $data = []): WebAlert
    {
        $alert = WebAlert::query()->create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        // Push is queued to avoid blocking user-facing requests.
        SendWebAlertPushJob::dispatch((int) $alert->id);

        return $alert;
    }

    public function getRecent(string $recipientType, int $recipientId, int $limit = 8)
    {
        return WebAlert::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(string $recipientType, int $recipientId): int
    {
        return WebAlert::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->count();
    }

    public function paginate(string $recipientType, int $recipientId, int $perPage = 20, string $status = 'all'): LengthAwarePaginator
    {
        return WebAlert::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->when($status === 'unread', fn($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead(string $recipientType, int $recipientId, int $alertId): bool
    {
        $alert = WebAlert::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereKey($alertId)
            ->first();

        if (! $alert) {
            return false;
        }

        if ($alert->read_at === null) {
            $alert->read_at = now();
            $alert->save();
        }

        return true;
    }

    public function markAllAsRead(string $recipientType, int $recipientId): int
    {
        return WebAlert::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}