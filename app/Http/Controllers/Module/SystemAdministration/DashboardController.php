<?php

namespace App\Http\Controllers\Module\SystemAdministration;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\SystemAdministration\NavigationService;
use App\Services\SystemAdministration\ServicePackageAdminService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ServicePackageAdminService $packageService,
        private readonly NavigationService $navigationService,
    ) {}

    public function index(): View
    {
        return view('system-administration.dashboard', [
            'summary' => $this->packageService->dashboardSummary(),
            'cards' => $this->navigationService->cards(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
