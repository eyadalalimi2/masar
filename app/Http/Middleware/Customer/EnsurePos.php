<?php

namespace App\Http\Middleware\Customer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePos
{
    public function handle(Request $request, Closure $next): Response
    {
        $pos = Auth::guard('pos')->user();

        if (! $pos || $pos->status !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}
