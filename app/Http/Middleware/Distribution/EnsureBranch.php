<?php

namespace App\Http\Middleware\Distribution;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = Auth::guard('branch')->user();

        if (! $account || $account->status !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}






