<?php

namespace App\Http\Controllers\Module\DoctorsDirectory;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\DoctorsDirectory\SpecialityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialityController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly SpecialityService $specialityService,
    ) {}

    public function index(): View
    {
        $pageData = $this->specialityService->indexPageData();

        return view('doctors-directory.specialities.index', [
            'cards' => $pageData['cards'],
            'summary' => $pageData['summary'],
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function departments(Request $request, int $speciality): View
    {
        $hospitalId = $request->integer('hospital') ?: null;
        $record = $this->specialityService->findForDepartmentsPage($speciality, $hospitalId);

        abort_if($record === null, 404);

        if ($record['showBranchPicker']) {
            return view('doctors-directory.specialities.show', [
                'speciality' => $record['speciality'],
                'overviewContent' => $record['overviewContent'],
                'hospitals' => $record['hospitals'],
                'homeRoute' => $this->homeRouteName(),
            ]);
        }

        return view('doctors-directory.specialities.departments', [
            'speciality' => $record['speciality'],
            'description' => $record['description'],
            'selectedHospital' => $record['selectedHospital'],
            'hospitals' => $record['hospitals'],
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
