<?php

namespace App\View\Composers;

use App\Services\Dashboard\NavigationService;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function __construct(
        private readonly NavigationService $navigation,
    ) {}

    public function compose(View $view): void
    {
        if (! session()->has('hr_user_id')) {
            return;
        }

        $view->with([
            'sidebarItems' => $this->navigation->sidebar(),
            'sidebarContext' => $this->navigation->activeSidebarContext(),
            'notifications' => $this->navigation->notifications(),
            'notificationCount' => $this->navigation->notificationCount(),
            'userName' => $this->navigation->userDisplayName(),
            'userInitials' => $this->navigation->userInitials(),
            'homeRoute' => $this->navigation->homeRouteName(),
            'homeRouteParams' => $this->navigation->homeRouteParams(),
            'homeUrl' => $this->navigation->homeUrl(),
            'currentRoute' => Route::currentRouteName(),
        ]);
    }
}
