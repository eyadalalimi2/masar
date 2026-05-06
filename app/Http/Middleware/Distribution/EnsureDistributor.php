<?php

namespace App\Http\Middleware\Distribution;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDistributor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('distributor')->user();

        if (! $user || $user->status !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}






