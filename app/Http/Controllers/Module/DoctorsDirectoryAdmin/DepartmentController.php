<?php

namespace App\Http\Controllers\Module\DoctorsDirectoryAdmin;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorsDirectoryAdmin\DepartmentIndexRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\StoreDepartmentRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\UpdateDepartmentRequest;
use App\Services\DoctorsDirectoryAdmin\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(DepartmentIndexRequest $request): View
    {
        $specialities = $this->departmentService->specialityOptions();
        $selectedSpecialityId = $request->specialityId();

        return view('doctors-directory-admin.departments.index', [
            'departments' => $this->departmentService->listPaginated(
                $request->search(),
                $selectedSpecialityId,
                $request->publish(),
            ),
            'specialities' => $specialities,
            'filters' => [
                'search' => $request->search(),
                'speciality' => $selectedSpecialityId ? (string) $selectedSpecialityId : '',
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

        return view('doctors-directory-admin.departments.create', [
            'specialities' => $this->departmentService->specialityOptions(),
            'departmentOptions' => $this->departmentService->departmentOptions(),
            'selectedSpecialityId' => $specialityFromQuery,
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $record = $this->departmentService->create(
            $request->specialityId(),
            $request->departmentId(),
            $request->publish(),
        );

        return redirect()
            ->route('modules.doctors-admin.departments.edit', $record->id)
            ->with('success', __('doctors_directory_admin.department_assignment_created'));
    }

    public function edit(int $section): View
    {
        $record = $this->departmentService->findForEdit($section);

        abort_if($record === null, 404);

        return view('doctors-directory-admin.departments.edit', [
            'section' => $record,
            'specialities' => $this->departmentService->specialityOptions(),
            'departmentOptions' => $this->departmentService->departmentOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, int $section): RedirectResponse
    {
        $record = $this->departmentService->findForEdit($section);

        abort_if($record === null, 404);

        $this->departmentService->update(
            $record,
            $request->specialityId(),
            $request->departmentId(),
            $request->publish(),
        );

        return redirect()
            ->route('modules.doctors-admin.departments.edit', $record->id)
            ->with('success', __('doctors_directory_admin.department_assignment_updated'));
    }

    public function publish(int $section): RedirectResponse
    {
        $record = $this->departmentService->findForEdit($section);

        abort_if($record === null, 404);

        $updated = $this->departmentService->togglePublish($record);

        $message = ((int) $updated->publish) === 1
            ? __('doctors_directory_admin.department_assignment_published')
            : __('doctors_directory_admin.department_assignment_unpublished');

        return redirect()
            ->route('modules.doctors-admin.departments.index')
            ->with('success', $message);
    }
}

