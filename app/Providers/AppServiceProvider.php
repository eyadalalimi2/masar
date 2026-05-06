<?php

namespace App\Providers;

use App\Services\Operations\OperationalAlertService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin-login', fn(Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('agent-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('branch-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('distributor-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('customer-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('consumer-login', fn(Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('pos-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('workshop-login', fn(Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('sensitive-writes', fn(Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            Log::channel('operations')->error('Queue job failed.', [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_name' => $event->job->resolveName(),
                'exception' => get_class($event->exception),
                'message' => $event->exception->getMessage(),
            ]);

            app(OperationalAlertService::class)->trigger(
                'queue_job_failed',
                'Queue job failed and needs attention.',
                [
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'job_name' => $event->job->resolveName(),
                    'exception' => get_class($event->exception),
                    'message' => $event->exception->getMessage(),
                    'severity' => 'critical',
                ],
                120
            );
        });

        Paginator::defaultView('pagination.previous');
        Paginator::defaultSimpleView('pagination.previous-simple');
    }
}
