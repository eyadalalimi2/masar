<?php

namespace App\Services\Notifications;

use App\Jobs\Notifications\DispatchAdminBroadcastJob;
use App\Models\Admin\Broadcast;
use App\Models\Customer\Consumer;
use App\Models\Customer\Customer;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\DistributorAccount;
use App\Models\Pos;
use App\Models\Supplier\Agent;

class AdminBroadcastService
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function dispatchBroadcast(Broadcast $broadcast): int
    {
        if (! $broadcast->is_active) {
            return 0;
        }

        if ($broadcast->dispatched_at !== null) {
            return 0;
        }

        $sent = $this->dispatch(
            (string) $broadcast->target_type,
            (string) $broadcast->title,
            (string) $broadcast->message,
            [
                'type' => 'admin_broadcast',
                'broadcast_id' => $broadcast->id,
                'target_type' => $broadcast->target_type,
            ]
        );

        $broadcast->forceFill(['dispatched_at' => now()])->save();

        return $sent;
    }

    public function dispatchDueScheduled(): array
    {
        $broadcasts = Broadcast::query()
            ->where('is_active', true)
            ->whereNull('dispatched_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        $broadcastsCount = 0;
        $recipientsCount = 0;

        foreach ($broadcasts as $broadcast) {
            $recipientsCount += $this->dispatchBroadcast($broadcast);
            $broadcastsCount++;
        }

        return [
            'broadcasts' => $broadcastsCount,
            'recipients' => $recipientsCount,
        ];
    }

    public function queueDueScheduled(): array
    {
        $broadcastIds = Broadcast::query()
            ->where('is_active', true)
            ->whereNull('dispatched_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(100)
            ->pluck('id');

        foreach ($broadcastIds as $broadcastId) {
            DispatchAdminBroadcastJob::dispatch((int) $broadcastId);
        }

        return [
            'queued_broadcasts' => (int) $broadcastIds->count(),
        ];
    }

    public function dispatch(string $targetType, string $title, string $message, array $data = []): int
    {
        $targets = $this->targetsFor($targetType);
        $sent = 0;

        foreach ($targets as [$recipientType, $recipientId]) {
            $this->webAlertService->create($recipientType, $recipientId, $title, $message, $data);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function targetsFor(string $targetType): array
    {
        $targets = [];

        if ($targetType === 'all' || $targetType === 'suppliers') {
            foreach (Agent::query()->pluck('id') as $id) {
                $targets[] = ['agent', (int) $id];
            }
        }

        if ($targetType === 'all' || $targetType === 'branches') {
            foreach (BranchAccount::query()->pluck('id') as $id) {
                $targets[] = ['branch_account', (int) $id];
            }
        }

        if ($targetType === 'all' || $targetType === 'distributors') {
            foreach (DistributorAccount::query()->pluck('id') as $id) {
                $targets[] = ['distributor_account', (int) $id];
            }
        }

        if ($targetType === 'all' || $targetType === 'customers') {
            foreach (Customer::query()->pluck('id') as $id) {
                $targets[] = ['customer', (int) $id];
            }

            foreach (Pos::query()->pluck('id') as $id) {
                $targets[] = ['pos_account', (int) $id];
            }
        }

        if ($targetType === 'all' || $targetType === 'consumers') {
            foreach (Consumer::query()->pluck('id') as $id) {
                $targets[] = ['consumer', (int) $id];
            }
        }

        return $targets;
    }
}
