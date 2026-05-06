<?php

namespace App\Traits\Notifications;

use App\Services\Notifications\FirebaseService;
use Illuminate\Support\Collection;

trait SendNotification
{
    protected function sendToUser(mixed $user, string $title, string $body, array $data = []): bool
    {
        $token = is_object($user) ? ($user->fcm_token ?? null) : null;

        if (! $token) {
            return false;
        }

        return app(FirebaseService::class)->sendNotification((string) $token, $title, $body, $data);
    }

    protected function sendToMany(iterable $users, string $title, string $body, array $data = []): int
    {
        $sent = 0;

        $items = $users instanceof Collection ? $users : collect($users);

        foreach ($items as $user) {
            if ($this->sendToUser($user, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }
}