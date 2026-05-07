<?php

namespace App\Http\Middleware\Admin;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            abort(403);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $permission = $this->mapRouteToPermission($routeName);

        if (str_starts_with($routeName, 'admin.') && $permission === null) {
            Log::channel('security_alerts')->critical('Denied admin route with unmapped permission.', [
                'admin_id' => (int) $admin->id,
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            abort(403, 'صلاحية هذا المسار غير معرفة.');
        }

        if ($this->isSensitiveWrite($request) && $permission === null) {
            Log::channel('security_alerts')->critical('Denied sensitive admin route with unmapped permission.', [
                'admin_id' => (int) $admin->id,
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            abort(403, 'صلاحية هذه العملية غير معرفة.');
        }

        if ($permission !== null && $admin instanceof Admin && ! $admin->hasPermission($permission)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }

    private function mapRouteToPermission(string $routeName): ?string
    {
        if ($routeName === '') {
            return null;
        }

        return match (true) {
            str_starts_with($routeName, 'admin.dashboard') => 'dashboard.view',
            $routeName === 'admin.logout' => 'dashboard.view',
            str_starts_with($routeName, 'admin.developer-profile') => 'dashboard.view',
            str_starts_with($routeName, 'admin.users') => 'users.manage',
            str_starts_with($routeName, 'admin.account-opening-excel') => 'organizations.manage',
            str_starts_with($routeName, 'admin.auth-verification') => 'users.verify',
            str_starts_with($routeName, 'admin.suppliers'),
            str_starts_with($routeName, 'admin.branches'),
            str_starts_with($routeName, 'admin.distributors'),
            str_starts_with($routeName, 'admin.commercial-stores'),
            str_starts_with($routeName, 'admin.customers'),
            str_starts_with($routeName, 'admin.workshops'),
            str_starts_with($routeName, 'admin.wholesale-traders'),
            str_starts_with($routeName, 'admin.consumers') => 'organizations.manage',
            str_starts_with($routeName, 'admin.products'),
            str_starts_with($routeName, 'admin.categories'),
            str_starts_with($routeName, 'admin.units'),
            str_starts_with($routeName, 'admin.variant-types'),
            str_starts_with($routeName, 'admin.variant-values'),
            str_starts_with($routeName, 'admin.production-years') => 'products.manage',
            str_starts_with($routeName, 'admin.orders') => 'orders.manage',
            str_starts_with($routeName, 'admin.delivery') => 'delivery.manage',
            str_starts_with($routeName, 'admin.inventory') => 'inventory.manage',
            str_starts_with($routeName, 'admin.payments'),
            str_starts_with($routeName, 'admin.accounts') => 'finance.manage',
            str_starts_with($routeName, 'admin.locations') => 'locations.manage',
            str_starts_with($routeName, 'admin.content') => 'content.manage',
            str_starts_with($routeName, 'admin.pricing') => 'pricing.manage',
            str_starts_with($routeName, 'admin.reports') => 'reports.view',
            str_starts_with($routeName, 'admin.tasks') => 'tasks.manage',
            str_starts_with($routeName, 'admin.settings'),
            str_starts_with($routeName, 'admin.payment-methods'),
            str_starts_with($routeName, 'admin.platform-release') => 'settings.manage',
            str_starts_with($routeName, 'admin.admins') => 'roles.manage',
            str_starts_with($routeName, 'admin.roles') => 'roles.manage',
            str_starts_with($routeName, 'admin.permission-groups') => 'roles.manage',
            str_starts_with($routeName, 'admin.audit-logs') => 'logs.view',
            str_starts_with($routeName, 'admin.notifications') => 'notifications.manage',
            str_starts_with($routeName, 'api.v1.admin.') => 'dashboard.view',
            default => null,
        };
    }

    private function isSensitiveWrite(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
