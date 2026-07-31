<?php

namespace App\Http\Controllers\Module\SystemAdministration;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemAdministration\PackageIndexRequest;
use App\Http\Requests\SystemAdministration\UpdatePackageRequest;
use App\Services\SystemAdministration\ServicePackageAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ServicePackageAdminService $packageService,
    ) {}

    public function index(PackageIndexRequest $request): View
    {
        return view('system-administration.packages.index', [
            'packages' => $this->packageService->listPaginated(
                $request->search(),
                $request->sectionId(),
                $request->publish(),
            ),
            'sectionOptions' => $this->packageService->sectionOptions(),
            'filters' => [
                'search' => $request->search(),
                'section' => $request->sectionId() ?? '',
                'publish' => $request->publish() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function edit(int $package): View
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        return view('system-administration.packages.edit', [
            'package' => $record,
            'sectionOptions' => $this->packageService->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function update(UpdatePackageRequest $request, int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $this->packageService->update(
            $record,
            $request->code(),
            $request->nameAr(),
            $request->nameEn(),
            $request->price(),
            $request->publish(),
        );

        return redirect()
            ->route('modules.system-admin.packages.edit', $record->id)
            ->with('success', __('system_administration.package_updated'));
    }

    public function publish(int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $updated = $this->packageService->togglePublish($record);

        $message = $updated->publish === '1'
            ? __('system_administration.package_published')
            : __('system_administration.package_unpublished');

        return redirect()
            ->route('modules.system-admin.packages.index')
            ->with('success', $message);
    }

    public function destroy(int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $this->packageService->delete($record);

        return redirect()
            ->route('modules.system-admin.packages.index')
            ->with('success', __('system_administration.package_deleted'));
    }
}
