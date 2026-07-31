<?php

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
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'auth.session' => \App\Http\Middleware\EnsureAuthSession::class,
            'otp.pending' => \App\Http\Middleware\EnsureOtpPending::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'permission.admin' => \App\Http\Middleware\EnsurePermissionAdministrator::class,
            'prevent.cache' => \App\Http\Middleware\PreventPageCaching::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('hm:cc-send-daily-reminders')
            ->dailyAt((string) config('hm.cc_notifications.reminders_at', '07:00'))
            ->timezone((string) config('hm.cc_notifications.timezone', 'Asia/Riyadh'))
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
