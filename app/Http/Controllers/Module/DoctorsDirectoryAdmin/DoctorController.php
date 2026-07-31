<?php

namespace App\Http\Controllers\Module\DoctorsDirectoryAdmin;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorsDirectoryAdmin\DoctorIndexRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\StoreDoctorAssignmentRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\StoreDoctorRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\UpdateDoctorRequest;
use App\Services\DoctorsDirectoryAdmin\DoctorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DoctorController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly DoctorService $doctorService,
    ) {}

    public function index(DoctorIndexRequest $request): View
    {
        $specialityId = $request->specialityId();

        return view('doctors-directory-admin.doctors.index', [
            'doctors' => $this->doctorService->listPaginated(
                $request->search(),
                $specialityId,
                $request->departmentId(),
                $request->publish(),
            ),
            'specialities' => $this->doctorService->specialityOptions(),
            'departments' => $this->doctorService->departmentOptions(),
            'filters' => [
                'search' => $request->search(),
                'speciality' => $specialityId ? (string) $specialityId : '',
                'department' => $request->departmentId() ? (string) $request->departmentId() : '',
                'publish' => $request->publish() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        $specialityFromQuery = request()->query('speciality');
        $specialityFromQuery = is_numeric($specialityFromQuery) ? (int) $specialityFromQuery : null;

        return view('doctors-directory-admin.doctors.create', [
            'specialities' => $this->doctorService->specialityOptions(),
            'countries' => $this->doctorService->countryOptions(),
            'selectedSpecialityId' => $specialityFromQuery,
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $doctor = $this->doctorService->create(
            $request->doctorAttributes(),
            $request->file('photo'),
        );

        return redirect()
            ->route('modules.doctors-admin.doctors.edit', $doctor->id)
            ->with('success', __('doctors_directory_admin.doctor_created'));
    }

    public function edit(int $doctor): View
    {
        $record = $this->doctorService->findForEdit($doctor);

        abort_if($record === null, 404);

        return view('doctors-directory-admin.doctors.edit', [
            'doctor' => $record,
            'specialities' => $this->doctorService->specialityOptions(),
            'countries' => $this->doctorService->countryOptions(),
            'departments' => $this->doctorService->departmentOptionsForSpeciality((int) $record->specialized_clinics_id),
            'previewUrl' => $this->doctorService->previewUrl($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function update(UpdateDoctorRequest $request, int $doctor): RedirectResponse
    {
        $record = $this->doctorService->findForEdit($doctor);

        abort_if($record === null, 404);

        $this->doctorService->update(
            $record,
            $request->doctorAttributes(),
            $request->file('photo'),
            $request->removePhoto(),
        );

        return redirect()
            ->route('modules.doctors-admin.doctors.edit', $record->id)
            ->with('success', __('doctors_directory_admin.doctor_updated'));
    }

    public function publish(int $doctor): RedirectResponse
    {
        $record = $this->doctorService->findForEdit($doctor);

        abort_if($record === null, 404);

        $updated = $this->doctorService->togglePublish($record);

        $message = $updated->publish === '1'
            ? __('doctors_directory_admin.doctor_published')
            : __('doctors_directory_admin.doctor_unpublished');

        return redirect()
            ->route('modules.doctors-admin.doctors.index')
            ->with('success', $message);
    }

    public function storeAssignment(StoreDoctorAssignmentRequest $request, int $doctor): RedirectResponse
    {
        $record = $this->doctorService->findForEdit($doctor);

        abort_if($record === null, 404);

        $this->doctorService->addAssignment($record->id, $request->departmentId());

        return redirect()
            ->route('modules.doctors-admin.doctors.edit', $record->id)
            ->with('success', __('doctors_directory_admin.doctor_assignment_added'));
    }

    public function destroyAssignment(int $doctor, int $assignment): RedirectResponse
    {
        $record = $this->doctorService->findForEdit($doctor);

        abort_if($record === null, 404);

        abort_unless($this->doctorService->removeAssignment($doctor, $assignment), 404);

        return redirect()
            ->route('modules.doctors-admin.doctors.edit', $record->id)
            ->with('success', __('doctors_directory_admin.doctor_assignment_removed'));
    }
}
