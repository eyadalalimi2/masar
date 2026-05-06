<?php

namespace App\Http\Middleware\Supplier;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupplier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('agent')->user();

        if (! $user || $user->status !== 'active' || ! $user->supplier) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }
}
