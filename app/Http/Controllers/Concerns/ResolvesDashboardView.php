<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Dashboard\NavigationService;

trait ResolvesDashboardView
{
    protected function userDisplayName(): string
    {
        return trim(session('hr_first_name', '').' '.session('hr_last_name', ''));
    }

    protected function homeRouteName(): string
    {
        return app(NavigationService::class)->homeRouteName();
    }
}
