<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Services\Operations\OperationalAlertService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.admin' => \App\Http\Middleware\Admin\EnsureAdmin::class,
            'ensure.admin.session' => \App\Http\Middleware\Admin\EnsureAdminSessionFresh::class,
            'ensure.admin.permission' => \App\Http\Middleware\Admin\EnsureAdminPermission::class,
            'admin.audit' => \App\Http\Middleware\Admin\LogAdminAction::class,
            'audit.action' => \App\Http\Middleware\Audit\LogSensitiveAction::class,
            'audit.context' => \App\Http\Middleware\Audit\InitializeAuditContext::class,
            'ensure.supplier' => \App\Http\Middleware\Supplier\EnsureSupplier::class,
            'ensure.distributor' => \App\Http\Middleware\Distribution\EnsureDistributor::class,
            'ensure.branch' => \App\Http\Middleware\Distribution\EnsureBranch::class,
            'ensure.customer' => \App\Http\Middleware\Customer\EnsureCustomer::class,
            'ensure.consumer' => \App\Http\Middleware\Customer\EnsureConsumer::class,
            'ensure.pos' => \App\Http\Middleware\Customer\EnsurePos::class,
            'ensure.workshop' => \App\Http\Middleware\Customer\EnsureWorkshop::class,
            'ensure.portal.permission' => \App\Http\Middleware\Security\EnsurePortalPermission::class,
        ]);

        // Global audit for non-read requests by authenticated actors.
        $middleware->append(\App\Http\Middleware\Audit\InitializeAuditContext::class);
        $middleware->append(\App\Http\Middleware\Security\ThrottleSensitiveWrites::class);
        $middleware->append(\App\Http\Middleware\Security\EnsurePortalSensitiveRoutePolicy::class);
        $middleware->append(\App\Http\Middleware\Audit\LogSensitiveAction::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => 'البيانات المرسلة غير صالحة.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'حدث خطأ في الطلب.',
                ], $exception->getStatusCode());
            }

            app(OperationalAlertService::class)->trigger(
                'unhandled_exception',
                'Unhandled exception reached JSON renderer.',
                [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'severity' => 'critical',
                ]
            );

            return response()->json([
                'message' => 'حدث خطأ غير متوقع في الخادم.',
            ], 500);
        });
    })->create();
