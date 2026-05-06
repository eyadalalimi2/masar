# Portal RBAC Rollout Plan (Staging -> Production)

## Goal

Transition portal authorization for non-admin guards from default grants fallback to explicit per-account grants using `portal_account_permissions`, with safe staged rollout and rollback.

## Scope

Guards covered:

- agent
- branch
- distributor
- customer
- consumer
- pos
- workshop

Key components already implemented:

- Permission resolver service: `App\\Services\\Security\\PortalPermissionService`
- Sensitive route policy middleware: `App\\Http\\Middleware\\Security\\EnsurePortalSensitiveRoutePolicy`
- Fallback switch: `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK`
- Grant management command: `php artisan permissions:portal-account {guard} {account_id} {permission} [--deny] [--revoke]`

## Prerequisites

1. Database migrations are applied.
2. Security logging/alerts channels are healthy.
3. On-call owner is assigned for rollout window.

## Rollout Strategy

Use two phases:

1. Phase A (Staging): strict mode verification.
2. Phase B (Production): controlled strict activation with monitoring and rollback trigger.

---

## Phase A - Staging

### A1) Keep fallback ON and seed explicit grants

- Set:
  - `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK=true`
- Seed explicit grants for active accounts by role profile.
- Example:
  - `php artisan permissions:portal-account agent 98011 orders.manage`
  - `php artisan permissions:portal-account pos 98061 api.access`

### A2) Negative/positive validation

- Positive checks: account with explicit grant can perform sensitive write.
- Negative checks: account with explicit deny is blocked.
- Revoke checks: after revoke, behavior follows fallback (while fallback is ON).

### A3) Enable strict mode in staging

- Set:
  - `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK=false`
- Run focused test set:
  - `php vendor/bin/phpunit -c phpunit.mysql.xml tests/Feature/PortalAccountPermissionCommandTest.php`
  - `php vendor/bin/phpunit -c phpunit.mysql.xml tests/Feature/PortalRolesIntegrationTest.php`
  - `php vendor/bin/phpunit -c phpunit.mysql.xml tests/Feature/BranchInventoryConcurrencyGuardTest.php`

### A4) Observe for 24-48 hours

Track:

- `sensitive_permission_denied`
- `sensitive_permission_missing`
- unexpected 403 spikes on portal writes

Exit criteria for production:

- No unexplained 403 increase.
- No unmapped sensitive route incidents.
- Critical business flows pass UAT.

---

## Phase B - Production

### B1) Pre-cutover (fallback ON)

- Keep:
  - `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK=true`
- Ensure explicit grants are seeded for all critical operational accounts.
- Run smoke tests for portal write operations.

### B2) Cutover window

- Switch to strict mode:
  - `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK=false`
- Deploy/reload config safely.
- Announce start of monitoring window.

### B3) Hypercare (first 2-4 hours)

Monitor in near-real-time:

- 403 rates on portal writes by guard and route.
- security alerts events above.
- operational KPIs impacted by write denials.

### B4) Stabilization (24 hours)

- Continue incident watch.
- Apply missing grants case-by-case using command.

---

## Rollback Plan

Immediate rollback trigger examples:

- sustained 403 surge affecting order, inventory, dispatch, or payment writes.
- repeated denial on verified valid users due to grant gaps.

Rollback action:

1. Set `OPERATIONS_USE_DEFAULT_GRANTS_FALLBACK=true`.
2. Reload config / restart workers if required.
3. Confirm flow restoration using smoke tests.
4. Keep collecting denied events and patch explicit grants before next strict attempt.

---

## Operational Runbook Snippets

Grant:

- `php artisan permissions:portal-account agent 98011 orders.manage`

Deny:

- `php artisan permissions:portal-account agent 98011 orders.manage --deny`

Revoke override:

- `php artisan permissions:portal-account agent 98011 orders.manage --revoke`

---

## Audit Checklist

Before strict mode ON:

1. All critical portal accounts mapped.
2. Deny/revoke behavior verified in staging.
3. Alert channels and dashboards confirmed.
4. Rollback owner assigned.

After strict mode ON:

1. No critical path blocked.
2. No unexplained 403 anomalies.
3. Incident notes captured for any permission hotfix.

---

## Ownership

- Security owner: defines permission profiles.
- Operations owner: executes rollout and monitors health.
- Engineering owner: applies fixes for route mapping/permission gaps.
