<?php

namespace App\Http\Controllers\Module\EmployeeServices;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\EmployeeServices\EmployeeServicesNavigationService;
use Illuminate\View\View;

class EmployeeServicesDashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly EmployeeServicesNavigationService $navigationService,
    ) {}

    public function index(): View
    {
        return view('employee-services.dashboard', [
            'cards' => $this->navigationService->cards(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
