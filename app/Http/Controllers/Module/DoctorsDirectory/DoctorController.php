<?php

namespace App\Http\Controllers\Module\DoctorsDirectory;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorsDirectory\DoctorIndexRequest;
use App\Services\DoctorsDirectory\DoctorService;
use App\Services\ServiceLocations\ServiceLocationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use stdClass;

class DoctorController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly DoctorService $doctorService,
        private readonly ServiceLocationService $serviceLocationService,
    ) {}

    public function branchIndex(DoctorIndexRequest $request, int $speciality, int $hospital): View
    {
        $context = $this->doctorService->findBranchDoctorsContext($speciality, $hospital);

        abort_if($context === null, 404);

        $allBranches = $request->allBranches();
        $filterOptions = $this->doctorService->branchDoctorsFilterOptions($speciality, $hospital, $allBranches);

        return view('doctors-directory.doctors.index', [
            'speciality' => $context['speciality'],
            'selectedHospital' => $context['hospital'],
            'branchLabel' => $context['branchLabel'],
            'doctors' => $this->doctorService->listBySpecialityAndHospital(
                $speciality,
                $allBranches ? null : $hospital,
                $request->name(),
                $request->code(),
                $allBranches,
            ),
            'filters' => [
                'name' => $request->name(),
                'code' => $request->code(),
                'all' => $allBranches,
            ],
            'hasFilters' => $request->hasFilters(),
            'clinicOptions' => $filterOptions['clinics'],
            'branchOptions' => $filterOptions['branches'],
            'selectedClinicId' => (string) $speciality,
            'selectedBranchId' => (string) $hospital,
            'homeRoute' => $this->homeRouteName(),
            'branchContext' => true,
            'indexRoute' => route('modules.doctors.branches.doctors.index', [$speciality, $hospital]),
            'backToBranchesRoute' => route('modules.doctors.specialities.departments', $speciality),
            'departmentsRoute' => route('modules.doctors.specialities.departments', [
                'speciality' => $speciality,
                'hospital' => $hospital,
            ]),
        ]);
    }

    public function index(DoctorIndexRequest $request, int $speciality, int $department): View
    {
        $departmentRecord = $this->doctorService->findDepartment($department, $speciality);

        abort_if($departmentRecord === null, 404);

        $hospitalId = $request->hospitalId();
        $allBranches = $request->allBranches();
        $scopedToHospital = $hospitalId !== null || $allBranches;
        $effectiveHospitalId = $allBranches ? null : $hospitalId;

        $branchContext = null;
        $branchLabel = null;
        $hospitalFilterContext = false;
        $filterOptions = ['clinics' => [], 'branches' => []];
        $selectedBranchId = '';
        $backToBranchesRoute = route('modules.doctors.specialities.departments', $speciality);
        $departmentsRoute = route('modules.doctors.specialities.departments', [
            'speciality' => $speciality,
            'hospital' => $hospitalId,
        ]);

        if ($hospitalId !== null && $hospitalId > 0) {
            $branchContextData = $this->doctorService->findBranchDoctorsContext($speciality, $hospitalId);

            if ($branchContextData !== null) {
                $hospitalFilterContext = true;
                $branchLabel = $branchContextData['branchLabel'];
                $filterOptions = $this->doctorService->departmentDoctorsFilterOptions(
                    $speciality,
                    $department,
                    $hospitalId,
                    $allBranches,
                );
                $selectedBranchId = (string) $hospitalId;
                $backToBranchesRoute = route('modules.doctors.specialities.departments', [
                    'speciality' => $speciality,
                    'hospital' => $hospitalId,
                ]);
            }
        }

        $indexRoute = route('modules.doctors.departments.doctors.index', array_filter([
            'speciality' => $speciality,
            'department' => $department,
            'hospital' => $hospitalId,
        ]));

        return view('doctors-directory.doctors.index', [
            'speciality' => $departmentRecord->speciality,
            'department' => $departmentRecord,
            'doctors' => $this->doctorService->listByDepartment(
                $speciality,
                $department,
                $request->name(),
                $request->code(),
                $request->specialization(),
                $effectiveHospitalId,
                $scopedToHospital,
            ),
            'filters' => [
                'name' => $request->name(),
                'code' => $request->code(),
                'all' => $allBranches,
            ],
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
            'indexRoute' => $indexRoute,
            'branchContext' => $branchContext,
            'hospitalFilterContext' => $hospitalFilterContext,
            'branchLabel' => $branchLabel,
            'clinicOptions' => $filterOptions['clinics'],
            'branchOptions' => $filterOptions['branches'],
            'selectedClinicId' => (string) $speciality,
            'selectedBranchId' => $selectedBranchId,
            'backToBranchesRoute' => $backToBranchesRoute,
            'departmentsRoute' => $departmentsRoute,
        ]);
    }

    public function opdIndex(
        DoctorIndexRequest $request,
        int $outpatientClinic,
        int $speciality,
    ): View {
        $record = $this->doctorService->findOpdSpeciality($outpatientClinic, $speciality);

        abort_if($record === null, 404);

        $departmentRecord = new stdClass();
        $departmentRecord->id = $outpatientClinic;
        $departmentRecord->outpatientClinic = $record['opd'];

        $opdLabel = $this->serviceLocationService->locationLabel($record['opd']);
        $departmentName = $record['speciality']->localizedName();

        return view('doctors-directory.doctors.index', [
            'speciality' => $record['speciality'],
            'department' => $departmentRecord,
            'doctors' => $this->doctorService->listByOpdSpeciality(
                $speciality,
                $outpatientClinic,
                $request->name(),
                $request->code(),
                $request->specialization(),
            ),
            'filters' => [
                'name' => $request->name(),
                'code' => $request->code(),
            ],
            'hasFilters' => $request->name() !== '' || $request->code() !== '',
            'homeRoute' => $this->homeRouteName(),
            'indexRoute' => route('modules.service-locations.opd.departments.doctors.index', [$outpatientClinic, $speciality]),
            'backToDepartmentsRoute' => route('modules.service-locations.show', $outpatientClinic),
            'serviceLocationContext' => true,
            'opdId' => $outpatientClinic,
            'opdLabel' => $opdLabel,
            'departmentName' => $departmentName,
        ]);
    }

    public function show(Request $request, int $doctor): View
    {
        $record = $this->doctorService->findDoctorDetail($doctor);

        abort_if($record === null, 404);

        $opdId = $request->integer('opd');
        $specialityId = $request->integer('speciality');
        $serviceLocationContext = $opdId > 0 && $specialityId > 0;

        $opdLabel = null;
        $departmentName = null;
        $doctorsListRoute = null;
        $opdShowRoute = null;

        if ($serviceLocationContext) {
            $opdRecord = $this->serviceLocationService->findForShow($opdId);

            if ($opdRecord !== null && (int) $record->specialized_clinics_id === $specialityId) {
                $opdLabel = $opdRecord['label'];
                $departmentName = $record->speciality?->localizedName();
                $doctorsListRoute = route('modules.service-locations.opd.departments.doctors.index', [$opdId, $specialityId]);
                $opdShowRoute = route('modules.service-locations.show', $opdId);
            } else {
                $serviceLocationContext = false;
            }
        }

        return view('doctors-directory.doctors.show', [
            'doctor' => $record,
            'homeRoute' => $this->homeRouteName(),
            'serviceLocationContext' => $serviceLocationContext,
            'opdLabel' => $opdLabel,
            'departmentName' => $departmentName,
            'doctorsListRoute' => $doctorsListRoute,
            'opdShowRoute' => $opdShowRoute,
        ]);
    }
}
