<?php

namespace App\Http\Controllers\Module\DoctorsDirectoryAdmin;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorsDirectoryAdmin\SpecialityIndexRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\StoreSpecialityRequest;
use App\Http\Requests\DoctorsDirectoryAdmin\UpdateSpecialityRequest;
use App\Services\DoctorsDirectoryAdmin\SpecialityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SpecialityController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly SpecialityService $specialityService,
    ) {}

    public function index(SpecialityIndexRequest $request): View
    {
        return view('doctors-directory-admin.specialities.index', [
            'specialities' => $this->specialityService->listPaginated(
                $request->search(),
                $request->publish(),
            ),
            'filters' => [
                'search' => $request->search(),
                'publish' => $request->publish() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('doctors-directory-admin.specialities.create', [
            'clinics' => $this->specialityService->clinicOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreSpecialityRequest $request): RedirectResponse
    {
        $speciality = $this->specialityService->create(
            $request->clinicId(),
            $request->subjectAr(),
            $request->subjectEn(),
            $request->publish(),
        );

        return redirect()
            ->route('modules.doctors-admin.specialities.edit', $speciality->id)
            ->with('success', __('doctors_directory_admin.speciality_created'));
    }

    public function edit(int $speciality): View
    {
        $record = $this->specialityService->findForEdit($speciality);

        abort_if($record === null, 404);

        return view('doctors-directory-admin.specialities.edit', [
            'speciality' => $record,
            'clinics' => $this->specialityService->clinicOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function update(UpdateSpecialityRequest $request, int $speciality): RedirectResponse
    {
        $record = $this->specialityService->findForEdit($speciality);

        abort_if($record === null, 404);

        $this->specialityService->update(
            $record,
            $request->clinicId(),
            $request->subjectAr(),
            $request->subjectEn(),
            $request->publish(),
        );

        return redirect()
            ->route('modules.doctors-admin.specialities.edit', $record->id)
            ->with('success', __('doctors_directory_admin.speciality_updated'));
    }

    public function publish(int $speciality): RedirectResponse
    {
        $record = $this->specialityService->findForEdit($speciality);

        abort_if($record === null, 404);

        $updated = $this->specialityService->togglePublish($record);

        $message = $updated->publish === '1'
            ? __('doctors_directory_admin.speciality_published')
            : __('doctors_directory_admin.speciality_unpublished');

        return redirect()
            ->route('modules.doctors-admin.specialities.index')
            ->with('success', $message);
    }
}
