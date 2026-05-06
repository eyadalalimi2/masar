<?php

namespace App\Http\Middleware\Customer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkshop
{
    public function handle(Request $request, Closure $next): Response
    {
        $workshop = Auth::guard('workshop')->user();

        if (! $workshop || $workshop->status !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}
