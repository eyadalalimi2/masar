<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebaseService
{
    public function sendNotification(string $token, string $title, string $body, array $data = []): bool
    {
        if ($token === '') {
            return false;
        }

        $payload = [];
        foreach ($data as $key => $value) {
            $payload[(string) $key] = is_scalar($value) || $value === null
                ? (string) ($value ?? '')
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($payload);

            $messaging->send($message);

            return true;
        } catch (Throwable $e) {
            Log::warning('FCM send failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}






