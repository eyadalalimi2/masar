<?php

namespace App\Http\Middleware\Admin;

use App\Models\Admin\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Auth::guard('admin')->check()) {
            return $response;
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        AuditLog::query()->create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => $request->route()?->getName() ?? 'admin.action',
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'status' => $response->getStatusCode(),
                'payload' => $request->except(['password', 'password_confirmation', 'token']),
            ],
        ]);

        return $response;
    }
}
