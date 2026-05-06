<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OperationalAlertService
{
    public function trigger(string $type, string $message, array $context = [], ?int $cooldownSeconds = null): void
    {
        $cooldown = $cooldownSeconds ?? max((int) config('operations.alerts.cooldown_seconds', 300), 0);
        $fingerprint = $this->fingerprint($type, $context);

        if ($cooldown > 0 && ! Cache::add('ops-alert:' . $fingerprint, 1, $cooldown)) {
            return;
        }

        $payload = array_merge($context, [
            'alert_type' => $type,
            'timestamp' => now()->toISOString(),
        ]);

        foreach ((array) config('operations.alerts.channels', ['operations_alerts']) as $channel) {
            Log::channel((string) $channel)->critical($message, $payload);
        }
    }

    private function fingerprint(string $type, array $context): string
    {
        $stable = [
            'type' => $type,
            'guard' => $context['guard'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'path' => $context['path'] ?? null,
            'route_name' => $context['route_name'] ?? null,
            'status_code' => $context['status_code'] ?? null,
            'exception' => $context['exception'] ?? null,
        ];

        return sha1(json_encode($stable));
    }
}
