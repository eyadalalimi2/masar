<?php

namespace App\Services\Pricing;

use App\Models\Admin\CommissionRule;

class CommissionEngineService
{
    public function resolveRule(string $entityType = 'global', ?int $entityId = null, ?string $regionKey = null): ?CommissionRule
    {
        $entityType = trim($entityType) !== '' ? $entityType : 'global';
        $regionKey = $this->normalizeRegion($regionKey);

        $query = CommissionRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($regionKey) {
                if ($regionKey !== null) {
                    $q->whereNull('region_key')->orWhere('region_key', $regionKey);

                    return;
                }

                $q->whereNull('region_key');
            })
            ->where(function ($q) {
                $now = now();

                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();

                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            });

        if ($entityId !== null && $entityType !== 'global') {
            $query->where(function ($q) use ($entityType, $entityId) {
                $q->where(function ($nested) use ($entityType, $entityId) {
                    $nested->where('entity_type', $entityType)->where('entity_id', $entityId);
                })->orWhere(function ($nested) use ($entityType) {
                    $nested->where('entity_type', $entityType)->whereNull('entity_id');
                })->orWhere('entity_type', 'global');
            });
        } else {
            $query->where('entity_type', 'global');
        }

        return $query
            ->orderByRaw("CASE WHEN entity_type = ? AND entity_id IS NOT NULL THEN 0 WHEN entity_type = ? THEN 1 WHEN entity_type = 'global' THEN 2 ELSE 3 END", [$entityType, $entityType])
            ->orderByRaw('CASE WHEN region_key IS NULL THEN 1 ELSE 0 END')
            ->orderBy('priority')
            ->orderByDesc('id')
            ->first();
    }

    public function calculate(float $baseAmount, string $entityType = 'global', ?int $entityId = null, ?string $regionKey = null): array
    {
        $rule = $this->resolveRule($entityType, $entityId, $regionKey);
        $baseAmount = max($baseAmount, 0);

        if (! $rule) {
            return [
                'rule_id' => null,
                'rule_name' => null,
                'commission_percent' => 0.0,
                'service_fee' => 0.0,
                'fixed_fee' => 0.0,
                'commission_value' => 0.0,
                'final_amount' => $baseAmount,
            ];
        }

        $commissionValue = round(($baseAmount * (float) $rule->commission_percent) / 100, 2);
        $serviceFee = (float) $rule->service_fee;
        $fixedFee = (float) $rule->fixed_fee;

        return [
            'rule_id' => (int) $rule->id,
            'rule_name' => (string) $rule->name,
            'commission_percent' => (float) $rule->commission_percent,
            'service_fee' => $serviceFee,
            'fixed_fee' => $fixedFee,
            'commission_value' => $commissionValue,
            'final_amount' => round($baseAmount + $commissionValue + $serviceFee + $fixedFee, 2),
        ];
    }

    private function normalizeRegion(?string $regionKey): ?string
    {
        if (! is_string($regionKey)) {
            return null;
        }

        $regionKey = trim($regionKey);

        return $regionKey === '' ? null : mb_strtolower($regionKey);
    }
}
