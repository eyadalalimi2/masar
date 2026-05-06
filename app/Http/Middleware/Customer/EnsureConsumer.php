<?php

namespace App\Http\Middleware\Customer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsumer
{
    public function handle(Request $request, Closure $next): Response
    {
        $consumer = Auth::guard('consumer')->user();

        if (! $consumer || $consumer->status !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}






