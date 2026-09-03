<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAuthSession;
use App\Http\Middleware\EnsureOtpPending;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePermissionAdministrator;
use App\Http\Middleware\PreventPageCaching;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare Tunnel forwards the original HTTPS request headers.
        $middleware->trustProxies(at: 'REMOTE_ADDR');

        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->validateCsrfTokens(except: ['sadq/callback']);

        $middleware->alias([
            'auth.session' => EnsureAuthSession::class,
            'otp.pending' => EnsureOtpPending::class,
            'permission' => EnsurePermission::class,
            'admin' => EnsureAdmin::class,
            'permission.admin' => EnsurePermissionAdministrator::class,
            'prevent.cache' => PreventPageCaching::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('hm:cc-send-daily-reminders')
            ->dailyAt((string) config('hm.cc_notifications.reminders_at', '07:00'))
            ->timezone((string) config('hm.cc_notifications.timezone', 'Asia/Riyadh'))
            ->withoutOverlapping();
        $schedule->command('hm:licenses-send-expiry-alerts')
            ->dailyAt((string) config('hm.licenses.scheduler_at', '07:15'))
            ->timezone((string) config('hm.licenses.timezone', 'Asia/Riyadh'))
            ->withoutOverlapping();
        $schedule->command('hm:gov-accounts-review-employee-status')
            ->dailyAt((string) config('hm.gov_accounts.employee_status.scheduler_at', '07:30'))
            ->timezone((string) config('hm.cc_notifications.timezone', 'Asia/Riyadh'))
            ->when(fn (): bool => (bool) config('hm.gov_accounts.employee_status.enabled', false))
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
