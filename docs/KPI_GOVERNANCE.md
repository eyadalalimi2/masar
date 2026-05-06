# KPI Governance Baseline

## Locked KPIs (Phase 2/3)

- BI: `sales_7d`, `sales_growth_percent_7d`, `delivered_orders_7d`, `orders_growth_percent_7d`, `pending_orders_total`, `sla_on_time_percent_30d`, `customer_growth_30d`, `customer_growth_delta_30d`.
- Monitoring: `failed_jobs_count`, `alerts_last_15m`, `active_delivery_now`, `write_pressure_indicator`, `sla_on_time_percent_30d`, `monitoring_overall_state`.

## KPI Change Policy

1. Any KPI definition change must include migration notes in PR description.
2. Any KPI removal requires replacement KPI and backward compatibility period.
3. Any KPI formula change must update corresponding feature tests and this file.

## Scope Control (Anti-Requirement-Creep)

1. Every new "smart" feature must map to an existing KPI or add a new KPI contract entry.
2. No feature enters implementation without: measurable KPI, rollback path, and test coverage plan.
3. Async-heavy features must define queue impact and alert threshold before release.

## Monitoring Loop Governance

1. Unified live loop command: `ops:monitor-live` (scheduled every minute).
2. Threshold changes in `config/operations.php` must be accompanied by KPI test updates.
3. Any critical monitoring state must have a cooldown policy and alert channel mapping.

## Review Gate

- Mandatory checks before merge:

1. Security policy coverage tests pass.
2. KPI endpoint contract tests pass.
3. Smoke tests for touched portal routes pass.
