<?php

namespace App\Services\Operations;

use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class OperationalMonitoringService
{
    public function __construct(private readonly OperationalAlertService $alertService) {}

    public function snapshot(bool $emitAlerts = false): array
    {
        $metrics = $this->collectMetrics();
        $thresholds = $this->thresholds();
        $health = $this->buildHealthState($metrics, $thresholds);

        if ($emitAlerts) {
            $this->emitThresholdAlerts($metrics, $health);
        }

        return [
            'metrics' => $metrics,
            'health' => $health,
            'thresholds' => $thresholds,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function collectMetrics(): array
    {
        $failedJobs = 0;
        try {
            $failedJobs = (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $failedJobs = 0;
        }

        $alertsLast15m = (int) DB::table('web_alerts')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $activeDeliveryNow = (int) Order::query()
            ->where('status', Order::STATUS_OUT_FOR_DELIVERY)
            ->count();

        $writePressure = (int) Order::query()
            ->where('updated_at', '>=', now()->subMinutes(5))
            ->count();

        $slaOnTime = (int) Order::query()
            ->where('status', Order::STATUS_DELIVERED)
            ->where('updated_at', '>=', now()->subDays(30))
            ->where('updated_at', '<=', DB::raw('DATE_ADD(created_at, INTERVAL 24 HOUR)'))
            ->count();

        $slaDelivered = (int) Order::query()
            ->where('status', Order::STATUS_DELIVERED)
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $slaOnTimePercent = $slaDelivered > 0 ? round(($slaOnTime / $slaDelivered) * 100, 2) : 0.0;

        return [
            'failed_jobs_count' => $failedJobs,
            'alerts_last_15m' => $alertsLast15m,
            'active_delivery_now' => $activeDeliveryNow,
            'write_pressure_indicator' => $writePressure,
            'sla_on_time_percent_30d' => $slaOnTimePercent,
        ];
    }

    private function thresholds(): array
    {
        return [
            'failed_jobs_warning' => (int) config('operations.thresholds.failed_jobs_warning', 5),
            'failed_jobs_critical' => (int) config('operations.thresholds.failed_jobs_critical', 20),
            'alerts_per_15m_warning' => (int) config('operations.thresholds.alerts_per_15m_warning', 15),
            'alerts_per_15m_critical' => (int) config('operations.thresholds.alerts_per_15m_critical', 40),
            'write_pressure_warning' => (int) config('operations.thresholds.write_pressure_warning', 80),
            'write_pressure_critical' => (int) config('operations.thresholds.write_pressure_critical', 140),
            'active_delivery_warning' => (int) config('operations.thresholds.active_delivery_warning', 120),
            'active_delivery_critical' => (int) config('operations.thresholds.active_delivery_critical', 220),
            'sla_on_time_warning_percent' => (float) config('operations.thresholds.sla_on_time_warning_percent', 85),
            'sla_on_time_critical_percent' => (float) config('operations.thresholds.sla_on_time_critical_percent', 70),
        ];
    }

    private function buildHealthState(array $metrics, array $thresholds): array
    {
        $checks = [
            'failed_jobs' => $this->classifyGreaterIsWorse(
                (float) $metrics['failed_jobs_count'],
                (float) $thresholds['failed_jobs_warning'],
                (float) $thresholds['failed_jobs_critical']
            ),
            'alerts_velocity' => $this->classifyGreaterIsWorse(
                (float) $metrics['alerts_last_15m'],
                (float) $thresholds['alerts_per_15m_warning'],
                (float) $thresholds['alerts_per_15m_critical']
            ),
            'write_pressure' => $this->classifyGreaterIsWorse(
                (float) $metrics['write_pressure_indicator'],
                (float) $thresholds['write_pressure_warning'],
                (float) $thresholds['write_pressure_critical']
            ),
            'delivery_load' => $this->classifyGreaterIsWorse(
                (float) $metrics['active_delivery_now'],
                (float) $thresholds['active_delivery_warning'],
                (float) $thresholds['active_delivery_critical']
            ),
            'sla_on_time' => $this->classifyLowerIsWorse(
                (float) $metrics['sla_on_time_percent_30d'],
                (float) $thresholds['sla_on_time_warning_percent'],
                (float) $thresholds['sla_on_time_critical_percent']
            ),
        ];

        $overall = 'ok';
        foreach ($checks as $check) {
            if ($check['state'] === 'critical') {
                $overall = 'critical';
                break;
            }

            if ($check['state'] === 'warning' && $overall !== 'critical') {
                $overall = 'warning';
            }
        }

        return [
            'overall' => $overall,
            'checks' => $checks,
        ];
    }

    private function classifyGreaterIsWorse(float $value, float $warning, float $critical): array
    {
        if ($value >= $critical) {
            return ['state' => 'critical', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
        }

        if ($value >= $warning) {
            return ['state' => 'warning', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
        }

        return ['state' => 'ok', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
    }

    private function classifyLowerIsWorse(float $value, float $warning, float $critical): array
    {
        if ($value <= $critical) {
            return ['state' => 'critical', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
        }

        if ($value <= $warning) {
            return ['state' => 'warning', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
        }

        return ['state' => 'ok', 'value' => $value, 'warning' => $warning, 'critical' => $critical];
    }

    private function emitThresholdAlerts(array $metrics, array $health): void
    {
        if (! isset($health['overall']) || ! in_array($health['overall'], ['warning', 'critical'], true)) {
            return;
        }

        $this->alertService->trigger(
            'monitoring_health_' . $health['overall'],
            'Operational monitoring detected a degraded state.',
            [
                'severity' => $health['overall'],
                'overall' => $health['overall'],
                'metrics' => $metrics,
                'checks' => $health['checks'] ?? [],
            ],
            (int) config('operations.thresholds.monitoring_alert_cooldown_seconds', 120)
        );
    }
}
