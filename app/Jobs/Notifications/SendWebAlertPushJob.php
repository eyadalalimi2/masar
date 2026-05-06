<?php

namespace App\Jobs\Notifications;

use App\Models\Distribution\BranchAccount;
use App\Models\Notifications\WebAlert;
use App\Models\User;
use App\Services\Notifications\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebAlertPushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $alertId)
    {
        $this->onQueue('notifications');
    }

    public function handle(FirebaseService $firebaseService): void
    {
        $alert = WebAlert::query()->find($this->alertId);
        if (! $alert) {
            return;
        }

        $token = $this->resolveToken((string) $alert->recipient_type, (int) $alert->recipient_id);
        if (! is_string($token) || trim($token) === '') {
            return;
        }

        $firebaseService->sendNotification(
            $token,
            (string) $alert->title,
            (string) $alert->body,
            [
                'alert_id' => (string) $alert->id,
                'recipient_type' => (string) $alert->recipient_type,
                'recipient_id' => (string) $alert->recipient_id,
            ]
        );
    }

    private function resolveToken(string $recipientType, int $recipientId): ?string
    {
        if ($recipientType === 'branch_account') {
            return BranchAccount::query()->whereKey($recipientId)->value('fcm_token');
        }

        if ($recipientType === 'user') {
            return User::query()->whereKey($recipientId)->value('fcm_token');
        }

        return null;
    }
}
