<?php

namespace App\Http\Controllers\Module\HospitalServices;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\HospitalServices\HospitalServiceCatalogService;
use Illuminate\View\View;

class HospitalServicesDashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly HospitalServiceCatalogService $catalogService,
    ) {}

    public function index(): View
    {
        return view('hospital-services.dashboard', [
            'sections' => $this->catalogService->navigationSectionCards(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
