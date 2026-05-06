<?php

namespace App\Jobs\Notifications;

use App\Models\Admin\Broadcast;
use App\Services\Notifications\AdminBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchAdminBroadcastJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $broadcastId)
    {
        $this->onQueue('notifications');
    }

    public function handle(AdminBroadcastService $adminBroadcastService): void
    {
        $broadcast = Broadcast::query()->find($this->broadcastId);
        if (! $broadcast) {
            return;
        }

        $adminBroadcastService->dispatchBroadcast($broadcast);
    }
}
