<?php

namespace App\Http\Controllers\Module\DoctorsDirectoryAdmin;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\DoctorsDirectoryAdmin\NavigationService;
use App\Services\DoctorsDirectoryAdmin\SpecialityService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly SpecialityService $specialityService,
        private readonly NavigationService $navigationService,
    ) {}

    public function index(): View
    {
        return view('doctors-directory-admin.dashboard', [
            'summary' => $this->specialityService->dashboardSummary(),
            'cards' => $this->navigationService->cards(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
