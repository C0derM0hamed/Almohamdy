<?php

namespace App\Http\Controllers\Module\ServiceLocations;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\ServiceLocations\ServiceLocationService;
use Illuminate\View\View;

class ServiceLocationController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ServiceLocationService $serviceLocationService,
    ) {}

    public function index(): View
    {
        return view('service-locations.index', [
            'cards' => $this->serviceLocationService->navigationCards(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function show(int $outpatientClinic): View
    {
        $record = $this->serviceLocationService->findForShow($outpatientClinic);

        abort_if($record === null, 404);

        return view('service-locations.show', [
            'location' => $record['location'],
            'label' => $record['label'],
            'duty' => $record['duty'],
            'departmentCards' => $record['departmentCards'],
            'floorName' => $this->serviceLocationService->localizedFloorName($record['duty']),
            'dutyDays' => $this->serviceLocationService->localizedDutyDays($record['duty']),
            'dutyTime' => $this->serviceLocationService->localizedDutyTime($record['duty']),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function floors(): View
    {
        return view('service-locations.floors', [
            'floors' => $this->serviceLocationService->floorsList(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function showFloor(int $floor): View
    {
        $record = $this->serviceLocationService->findFloorForShow($floor);

        abort_if($record === null, 404);

        return view('service-locations.floor-show', [
            'floor' => $record['floor'],
            'label' => $record['label'],
            'items' => $record['items'],
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function supportServices(int $outpatientClinic): View
    {
        $record = $this->serviceLocationService->findSupportServicesForOpd($outpatientClinic);

        abort_if($record === null, 404);

        return view('service-locations.support-services', [
            'location' => $record['location'],
            'label' => $record['label'],
            'items' => $record['items'],
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
