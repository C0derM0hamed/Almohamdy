<?php

namespace App\Http\Controllers\Module\HospitalServices;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\HospitalServices\ServiceCatalogIndexRequest;
use App\Services\HospitalServices\HospitalServiceCatalogService;
use Illuminate\View\View;

class ServiceSectionController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly HospitalServiceCatalogService $catalogService,
    ) {}

    public function show(ServiceCatalogIndexRequest $request, int $section): View
    {
        $record = $this->catalogService->findSection($section);

        abort_if($record === null, 404);

        return view('hospital-services.sections.show', [
            'section' => $record,
            'packages' => $this->catalogService->listSectionPackages($section, $request->search()),
            'sectionOptions' => $this->catalogService->sectionFilterOptions($section),
            'cardLayout' => $this->catalogService->sectionCardLayout($section),
            'isAgreementSection' => $this->catalogService->isAgreementSection($section),
            'search' => $request->search(),
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
