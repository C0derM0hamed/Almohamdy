<?php

namespace App\Providers;

use App\Services\Auth\PermissionService;
use App\Services\GovAccounts\EmployeeStatusProviderInterface;
use App\Services\GovAccounts\NullEmployeeStatusProvider;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use App\Support\HmAssets;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use App\View\Composers\AppLayoutComposer;
use Illuminate\Foundation\Console\ServeCommand;
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
        $this->app->bind(EmployeeStatusProviderInterface::class, NullEmployeeStatusProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->passLocalPhpIniToArtisanServe();

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

    /**
     * The local PHP wrapper loads pdo_mysql via PHPRC. Artisan serve
     * otherwise starts php.bin without that ini and login fails.
     */
    private function passLocalPhpIniToArtisanServe(): void
    {
        foreach (['PHPRC', 'PHP_INI_SCAN_DIR', 'LD_LIBRARY_PATH'] as $key) {
            if (! in_array($key, ServeCommand::$passthroughVariables, true)) {
                ServeCommand::$passthroughVariables[] = $key;
            }

            $value = getenv($key);
            if ($value !== false && ! isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}
