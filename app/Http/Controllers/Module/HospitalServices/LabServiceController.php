<?php

namespace App\Http\Controllers\Module\HospitalServices;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\HospitalServices\ServiceCatalogIndexRequest;
use App\Services\HospitalServices\HospitalServiceCatalogService;
use Illuminate\View\View;

class LabServiceController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly HospitalServiceCatalogService $catalogService,
    ) {}

    public function index(ServiceCatalogIndexRequest $request): View
    {
        return view('hospital-services.packages.index', [
            'pageKey' => 'lab',
            'packages' => $this->catalogService->listLabPackages($request->search()),
            'search' => $request->search(),
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
