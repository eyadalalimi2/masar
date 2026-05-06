<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesParallelDashboardSessions
{
    protected function regenerateForParallelDashboards(Request $request): void
    {
        if (config('development.parallel_dashboards', false)) {
            // Dev-only: rotate session ID without changing CSRF token,
            // so already-open login forms in other dashboards do not fail with 419.
            $request->session()->migrate(true);

            return;
        }

        $request->session()->regenerate();
    }

    protected function invalidateForParallelDashboards(Request $request): void
    {
        if (config('development.parallel_dashboards', false)) {
            // Dev-only mode: logout current guard without clearing other guard sessions.
            return;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}