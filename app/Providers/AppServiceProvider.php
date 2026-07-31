<?php

namespace App\Providers;

use App\Support\HmAssets;
use App\Services\Auth\PermissionService;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use App\View\Composers\AppLayoutComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        Paginator::useBootstrapFive();

        View::share('hmAssetVersion', HmAssets::version());

        View::composer('layouts.app', AppLayoutComposer::class);

        foreach (WorkAbsenceNotificationPermissions::all() as $permission) {
            Gate::define($permission, fn () => app(PermissionService::class)->can($permission));
        }

        foreach (EmployeeLeavePermissions::all() as $permission) {
            Gate::define($permission, fn () => app(PermissionService::class)->can($permission));
        }
    }
}
